<?php

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\Curl;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Helper\OAuth\Lib\AESEncryption;

/**
 * Handles all the licensing related actions. Premium version handles
 * processing of customer verify license key form. Checks if the
 * license key entered by the user is a valid license key for his
 * account or not. If so then activate his license.
 *
 * Also takes care of removing the current license from the site.
 */
class LKAction extends BaseAdminAction
{
    private $REQUEST;

    /**
     * Execute function to execute the classes function.
     * Handles the removing the configured license and customer
     * account from the module by removing the necessary keys
     * and feeing the key.
     *
     * @throws \Exception
     */
    public function removeAccount()
    {
        $this->oauthUtility->log_debug("LKAction: removeAccount");
        if ($this->oauthUtility->micr()) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_EMAIL, '');
                       $this->oauthUtility->setStoreConfig(OAuthConstants::CUSTOMER_KEY, '');
                       $this->oauthUtility->setStoreConfig(OAuthConstants::API_KEY, '');
            $this->oauthUtility->setStoreConfig(OAuthConstants::TOKEN, '');
            $this->oauthUtility->setStoreConfig(OAuthConstants::REG_STATUS, OAuthConstants::STATUS_VERIFY_LOGIN);
            $this->oauthUtility->removeSignInSettings();
        }
    }

    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
        $this->REQUEST = $request;
        return $this;
    }

    /* ===================================================================================================
                THE FUNCTIONS BELOW ARE FREE PLUGIN SPECIFIC AND DIFFER IN THE PREMIUM VERSION
       ===================================================================================================
     */

    public function execute()
    {
        /** implemented in premium version */
    }
}
