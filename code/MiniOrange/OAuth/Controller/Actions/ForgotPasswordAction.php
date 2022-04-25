<?php

namespace MiniOrange\OAuth\Controller\Actions;

use Magento\Framework\App\Action\HttpPostActionInterface;
use MiniOrange\OAuth\Helper\Curl;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Helper\Exception\AccountAlreadyExistsException;
use MiniOrange\OAuth\Helper\Exception\NotRegisteredException;

/**
 * Handles processing of Forgot Password Form.
 *
 * The main function of this action class is to
 * send a forgot password request to the user by
 * calling the forgot_password curl.
 */
class ForgotPasswordAction extends BaseAdminAction implements HttpPostActionInterface
{
    private $REQUEST;
    
    /**
     * Execute function to execute the classes function.
     *
     * @throws \Exception
     */
    public function execute()
    {
        $this->oauthUtility->log_debug("ForgotPasswordAction: execute");
        $this->checkIfRequiredFieldsEmpty(['email'=>$this->REQUEST]);
        $email = $this->REQUEST['email'];
        $customerKey = $this->oauthUtility->getStoreConfig(OAuthConstants::CUSTOMER_KEY);
        $apiKey = $this->oauthUtility->getStoreConfig(OAuthConstants::API_KEY);
        $content = json_decode(Curl::forgot_password($email, $customerKey, $apiKey), true);
        if (strcasecmp($content['status'], 'SUCCESS') == 0) {
            $this->messageManager->addSuccessMessage(OAuthMessages::PASS_RESET);
        } else {
            $this->messageManager->addErrorMessage(OAuthMessages::PASS_RESET_ERROR);
        }

            $this->oauthUtility->flushCache("ForgotPasswordAction : ");
    }


    /** Setter for the request Parameter */
    public function setRequestParam($request)
    {
        $this->REQUEST = $request;
        return $this;
    }
}
