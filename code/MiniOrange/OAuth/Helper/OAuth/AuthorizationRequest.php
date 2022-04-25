<?php

namespace MiniOrange\OAuth\Helper\OAuth;

use MiniOrange\OAuth\Helper\OAuth\SAML2Utilities;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\Exception\InvalidRequestInstantException;
use MiniOrange\OAuth\Helper\Exception\InvalidRequestVersionException;
use MiniOrange\OAuth\Helper\Exception\MissingIssuerValueException;

/**
 * This class is used to generate our AuthnRequest string.
 *
 */
class AuthorizationRequest
{

   
    private $clientID;
    private $scope;
    private $authorizeURL;
    private $responseType;
    private $redirectURL;

    public function __construct($clientID, $scope, $authorizeURL, $responseType, $redirectURL)
    {
        // all values required in the authn request are set here
        $this->clientID = $clientID;
        $this->scope = $scope;
        $this->authorizeURL = $authorizeURL;
        $this->responseType = $responseType;
        $this->redirectURL = $redirectURL;
    }

    /**
     * This function is called to generate our authnRequest. This is an internal
     * function and shouldn't be called directly. Call the @build function instead.
     * It returns the string format of the XML and encode it based on the sso
     * binding type.
     *
     * @todo - Have to convert this so that it's not a string value but an XML document
     */
    private function generateRequest()
    {
        
        $requestStr = "";
        $state = base64_encode($this->clientID);

        if (!(strpos($this->authorizeURL, '?') !== false)) {
            // ? NOT FOUND
            $requestStr .= '?';
        }
        
        $requestStr .= 'client_id=' . $this->clientID . '&scope=' . urlencode($this->scope) .
                       '&redirect_uri=' . urlencode($this->redirectURL) . '&response_type=' . $this->responseType . '&state=' . $state;
        
        return $requestStr;
    }


    /**
     * This function is used to build our AuthnRequest. Deflate
     * and encode the AuthnRequest string if the sso binding
     * type is empty or is of type HTTPREDIRECT.
     */
    public function build()
    {
        $requestStr = $this->generateRequest();
        return $requestStr;
    }
}
