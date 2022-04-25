<?php

namespace MiniOrange\OAuth\Controller\Adminhtml\Signinsettings;

use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthMessages;
use MiniOrange\OAuth\Helper\OAuth\SAML2Utilities;
use MiniOrange\OAuth\Controller\Actions\BaseAdminAction;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * This class handles the action for endpoint: mooauth/signinsettings/Index
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
     * This function gets and prepares all our SP config data from the
     * database. It's called when you visis the moasaml/signinsettings/Index
     * URL. It prepares all the values required on the SP setting
     * page in the backend and returns the block to be displayed.
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        try {
            $params = $this->getRequest()->getParams(); //get params

            // check if form options are being saved
            if ($this->isFormOptionBeingSaved($params)) {
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
        $mo_oauth_show_customer_link = isset($params['mo_oauth_show_customer_link']) ? 1 : 0;
        $mo_oauth_show_admin_link = isset($params['mo_oauth_show_admin_link']) ? 1 : 0;

        $this->oauthUtility->setStoreConfig(OAuthConstants::SHOW_CUSTOMER_LINK, $mo_oauth_show_customer_link);
        $this->oauthUtility->setStoreConfig(OAuthConstants::SHOW_ADMIN_LINK, $mo_oauth_show_admin_link);
    }


    /**
     * Is the user allowed to view the Sign in Settings.
     * This is based on the ACL set by the admin in the backend.
     * Works in conjugation with acl.xml
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed(OAuthConstants::MODULE_DIR.OAuthConstants::MODULE_SIGNIN);
    }
}
