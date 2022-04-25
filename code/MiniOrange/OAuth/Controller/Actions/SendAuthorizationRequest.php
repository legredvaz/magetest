<?php

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\OAuth\AuthorizationRequest;
use MiniOrange\OAuth\Helper\OAuthConstants;

/**
 * Handles generation and sending of AuthnRequest to the IDP
 * for authentication. AuthnRequest is generated and user is
 * redirected to the IDP for authentication.
 */
class sendAuthorizationRequest extends BaseAction
{

    /**
     * Execute function to execute the classes function.
     * @throws \Exception
     */
    public function execute()
    {
        $this->oauthUtility->log_debug("SendAuthorizationRequest: execute");
        $params = $this->getRequest()->getParams();  //get params
        $relayState = array_key_exists('relayState', $params) ? $params['relayState'] : '/';

        if ($relayState == OAuthConstants::TEST_RELAYSTATE) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::IS_TEST, true);
            $this->oauthUtility->flushCache();
        }

        if (!$this->oauthUtility->isOAuthConfigured()) {
            return;
        }

        //get required values from the database
        $clientID = $this->oauthUtility->getStoreConfig(OAuthConstants::CLIENT_ID);
        $scope = $this->oauthUtility->getStoreConfig(OAuthConstants::SCOPE);
        $authorizeURL =  $this->oauthUtility->getStoreConfig(OAuthConstants::AUTHORIZE_URL);
        $responseType = OAuthConstants::CODE;
            $redirectURL = $this->oauthUtility->getCallBackUrl();

        //generate the authorization request
        $authorizationRequest = (new AuthorizationRequest($clientID, $scope, $authorizeURL, $responseType, $redirectURL))->build();
        
        // send oauth request over
        return $this->sendHTTPRedirectRequest($authorizationRequest, $authorizeURL);
    }
}
