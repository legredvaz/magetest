<?php

namespace MiniOrange\OAuth\Controller\Actions;

use Exception;
use Magento\Framework\App\Action\Context;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuth\AccessTokenRequest;
use MiniOrange\OAuth\Helper\OAuth\AccessTokenRequestBody;
use MiniOrange\OAuth\Helper\Curl;
use miniorange\OAuth\Controller\Actions\MathBigInteger;
use MiniOrange\OAuth\Helper\OAuthUtility;

/**
 * Handles reading of Responses from the IDP. Read the SAML Response
 * from the IDP and process it to detect if it's a valid response from the IDP.
 * Generate a SAML Response Object and log the user in. Update existing user
 * attributes and groups if necessary.
 */
class ReadAuthorizationResponse extends BaseAction
{
    private $REQUEST;
    private $POST;
    private $processResponseAction;

    public function __construct(
        Context $context,
        OAuthUtility $oauthUtility,
        ProcessResponseAction $processResponseAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->processResponseAction = $processResponseAction;
        parent::__construct($context, $oauthUtility);
    }


/**
 * Execute function to execute the classes function.
 * @throws Exception
 */
    public function execute()
    {
        $this->oauthUtility->log_debug("ReadAuthorizationResponse: execute");
        // read the response
        $params = $this->getRequest()->getParams();
        
        
        if (!isset($params['code'])) {
            
            if (isset($params['error'])) {
                return $this->sendHTTPRedirectRequest('?error='.urlencode($params['error']), $this->oauthUtility->getBaseUrl());
            }  
            return $this->sendHTTPRedirectRequest('?error=code+not+received', $this->oauthUtility->getBaseUrl());
        }
        

        $authorizationCode = $params['code'];

        

        //get required values from the database
        $clientID = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_ID);
        $clientSecret = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_SECRET);
        $grantType = OAuthConstants::GRANT_TYPE;
        $accessTokenURL =  $this->oauthUtility->getStoreConfig(OAuthConstants::ACCESSTOKEN_URL);
        $redirectURL = $this->oauthUtility->getCallBackUrl();


        $header = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_HEADER);
        $body = $this->oauthUtility->getStoreConfig(OAuthConstants::SEND_BODY);

        if($header == 1 && $body == 0)
        {
            $accessTokenRequest = (new AccessTokenRequestBody($grantType, $redirectURL, $authorizationCode))->build();
        }

        //generate the accessToken request
        else
        $accessTokenRequest = (new AccessTokenRequest($clientID, $clientSecret, $grantType, $redirectURL, $authorizationCode))->build();

        //send the accessToken request
        $accessTokenResponse = Curl::mo_send_access_token_request($accessTokenRequest, $accessTokenURL, $clientID, $clientSecret);

        // todo: if access token response has an error
       
        // if access token endpoint returned a success response
       $accessTokenResponseData = json_decode($accessTokenResponse, 'true');
        if (isset($accessTokenResponseData['access_token'])) {
            $accessToken = $accessTokenResponseData['access_token'];
            $userInfoURL = $this->oauthUtility->getStoreConfig(OAuthConstants::GETUSERINFO_URL);

            $header = "Bearer " . $accessToken;
            $authHeader =  [
                "Authorization: $header"
            ];

            $userInfoResponse = Curl::mo_send_user_info_request($userInfoURL, $authHeader);
            $userInfoResponseData = json_decode($userInfoResponse, 'true');
        } elseif (isset($accessTokenResponseData['id_token'])) {
            $idToken = $accessTokenResponseData['id_token'];
            if (!empty($idToken)) {
                $x509_cert = $this->oauthUtility->getStoreConfig(OAuthConstants::X509CERT);
                $idTokenArray = explode(".", $idToken);
                if (sizeof($idTokenArray)>2) {
                    $jwks_uri = trim($x509_cert);
                    $jwkeys = json_decode(file_get_contents($jwks_uri))->keys[0];
                    
                    $JWTComponents = $this->decodeJWT($idToken);
                    
                    if (!$this->verifySign($JWTComponents, $jwkeys)) {
                        return $this->getResponse()->setBody("Invalid signature received.");
                    }        
                    $userInfoResponseData = $idTokenArray[1];
                    $userInfoResponseData = json_decode(base64_decode($userInfoResponseData));   
                } else {
                    return $this->getResponse()->setBody("Invalid response. Please try again.");
                }
            }
        } else {

            return $this->getResponse()->setBody("Invalid response. Please try again.");
        }
            
        if (empty($userInfoResponseData)) {
            return $this->getResponse()->setBody("Invalid response. Please try again.");
        }
        $this->processResponseAction->setUserInfoResponse($userInfoResponseData)->execute();
    }

    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
        $this->REQUEST = $request;
        return $this;
    }


    /** Setter for the post Parameter */
    public function setPostParam($post)
    {
        $this->POST = $post;
        return $this;
    }
    
    
    public function verifySign($JWTComponents, $jwkeys)
    {
        $this->oauthUtility->log_debug("ReadAuthorizationResponse: verifySign");
        $rsa = new CryptRSA();
        $rsa->loadKey([
                'n' => new MathBigInteger($this->get_base64_from_url($jwkeys->n), 256),
                'e' => new MathBigInteger($this->get_base64_from_url($jwkeys->e), 256)
        ]);
        $rsa->setHash('sha256');
        $rsa->setSignatureMode(CRYPT_RSA_SIGNATURE_PKCS1);
        return $rsa->verify($JWTComponents['data'], $JWTComponents['sign']) ? true : false;
    }

    public function get_base64_from_url($b64url)
    {
        return base64_decode(str_replace(['-','_'], ['+','/'], $b64url));
    }

    public function decodeJWT($JWT)
    {
        $pieces = explode(".", $JWT);
        $header = json_decode($this->get_base64_from_url($pieces[0]));
        $payload = json_decode($this->get_base64_from_url($pieces[1]));
        ;
        $sign = $this->get_base64_from_url($pieces[2]);
        
        return [
            'header' => $header,
            'payload' => $payload,
            'sign' => $sign,
            'data' => $pieces[0].".".$pieces[1],
        ];
    }
}
