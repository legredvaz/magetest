<?php

namespace MiniOrange\OAuth\Controller\Adminhtml\OAuthsettings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Helper\OAuth\SAML2Utilities;
use MiniOrange\OAuth\Controller\Actions\BaseAdminAction;

/**
 * This class handles the action for endpoint: mooauth/oauthsettings/Index
 * Extends the \Magento\Backend\App\Action for Admin Actions which
 * inturn extends the \Magento\Framework\App\Action\Action class necessary
 * for each Controller class
 */
 
class Index extends BaseAdminAction implements HttpPostActionInterface, HttpGetActionInterface
{
     /**
      * The first function to be called when a Controller class is invoked.
      * Usually, has all our controller logic. Returns a view/page/template
      * to be shown to the users.
      *
      * This function gets and prepares all our OAuth config data from the
      * database. It's called when you visis the mooauth/oauthsettings/Index
      * URL. It prepares all the values required on the SP setting
      * page in the backend and returns the block to be displayed.
      *
      * @return \Magento\Framework\View\Result\Page
      */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams(); //get params
           
            // check if form options are being saved
            if ($this->isFormOptionBeingSaved($params)) {
                 // check if required values have been submitted
                $this->checkIfRequiredFieldsEmpty(['mo_oauth_app_name'=>$params,'mo_oauth_client_id'=>$params,
                                                        'mo_oauth_client_secret'=>$params,'mo_oauth_scope'=>$params,
                                                                                     'mo_oauth_authorize_url'=>$params,'mo_oauth_accesstoken_url'=>$params,
                                                                                                                'mo_oauth_getuserinfo_url'=>$params]);
                      $this->processValuesAndSaveData($params);
                $this->oauthUtility->flushCache();
                $this->messageManager->addSuccessMessage(OAuthMessages::SETTINGS_SAVED);
                $this->oauthUtility->reinitConfig();
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            $this->logger->debug($e->getMessage());
        }
        
        // generate page
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__(OAuthConstants::MODULE_TITLE));
        return $resultPage;
    }
     
     /**
      * Process Values being submitted and save data in the database.
      */
    private function processValuesAndSaveData($params)
    {
        $mo_oauth_app_name        = trim($params['mo_oauth_app_name']);
        $mo_oauth_client_id       = trim($params['mo_oauth_client_id']);
              $mo_oauth_client_secret   = trim($params['mo_oauth_client_secret']);
              $mo_oauth_scope           = trim($params['mo_oauth_scope']);
              $mo_oauth_authorize_url   = trim($params['mo_oauth_authorize_url']);
              $mo_oauth_accesstoken_url = trim($params['mo_oauth_accesstoken_url']);
              $mo_oauth_getuserinfo_url = trim($params['mo_oauth_getuserinfo_url']);
              $send_header = isset($params['send_header']) ? 1 : 0;
              $send_body = isset($params['send_body']) ? 1 : 0;
        $this->oauthUtility->setStoreConfig(OAuthConstants::APP_NAME, $mo_oauth_app_name);
        $this->oauthUtility->setStoreConfig(OAuthConstants::CLIENT_ID, $mo_oauth_client_id);
        $this->oauthUtility->setStoreConfig(OAuthConstants::CLIENT_SECRET, $mo_oauth_client_secret);
        $this->oauthUtility->setStoreConfig(OAuthConstants::SCOPE, $mo_oauth_scope);
        $this->oauthUtility->setStoreConfig(OAuthConstants::AUTHORIZE_URL, $mo_oauth_authorize_url);
        $this->oauthUtility->setStoreConfig(OAuthConstants::ACCESSTOKEN_URL, $mo_oauth_accesstoken_url);
        $this->oauthUtility->setStoreConfig(OAuthConstants::GETUSERINFO_URL, $mo_oauth_getuserinfo_url);
        $this->oauthUtility->setStoreConfig(OAuthConstants::SEND_HEADER,$send_header);
        $this->oauthUtility->setStoreConfig(OAuthConstants::SEND_BODY,$send_body);
    }
    
     /**
      * Is the user allowed to view the Service Provider settings.
      * This is based on the ACL set by the admin in the backend.
      * Works in conjugation with acl.xml
      *
      * @return bool
      */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(OAuthConstants::MODULE_DIR.OAuthConstants::MODULE_OAUTHSETTINGS);
    }
}
