<?php

namespace MiniOrange\OAuth\Controller\Actions;

use Magento\Authorization\Model\ResourceModel\Role\Collection;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerFactory;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseFactory;
use Magento\Framework\Math\Random;
use Magento\Store\Model\StoreManagerInterface;
use Magento\User\Model\User;
use Magento\User\Model\UserFactory;
use MiniOrange\OAuth\Helper\Exception\MissingAttributesException;
use MiniOrange\OAuth\Helper\OAuthConstants;
use MiniOrange\OAuth\Helper\OAuthUtility;

/**
 * This action class processes the user attributes coming in
 * the SAML response to either log the customer or admin in
 * to their respective dashboard or create a customer or admin
 * based on the default role set by the admin and log them in
 * automatically.
 */
class ProcessUserAction extends BaseAction
{
    private $attrs;
    private $flattenedattrs;
    private $userEmail;
    private $checkIfMatchBy;
    private $defaultRole;
    private $emailAttribute;
    private $usernameAttribute;
    private $firstNameKey;
    private $lastNameKey;

    private $userGroupModel;
    private $adminRoleModel;
    private $adminUserModel;
    private $customerModel;
    private $customerLoginAction;
    private $responseFactory;
    private $customerFactory;
    private $userFactory;
    private $randomUtility;

    public function __construct(
        Context $context,
        OAuthUtility $oauthUtility,
        \Magento\Customer\Model\ResourceModel\Group\Collection $userGroupModel,
        Collection $adminRoleModel,
        User $adminUserModel,
        Customer $customerModel,
        StoreManagerInterface $storeManager,
        ResponseFactory $responseFactory,
        CustomerLoginAction $customerLoginAction,
        CustomerFactory $customerFactory,
        UserFactory $userFactory,
        Random $randomUtility
    ) {
        $this->emailAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_EMAIL);
        $this->emailAttribute = $oauthUtility->isBlank($this->emailAttribute) ? OAuthConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_USERNAME);
        $this->usernameAttribute = $oauthUtility->isBlank($this->usernameAttribute) ? OAuthConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->firstNameKey = $oauthUtility->getStoreConfig(OAuthConstants::MAP_FIRSTNAME);
        $this->firstNameKey = $oauthUtility->isBlank($this->firstNameKey) ? OAuthConstants::DEFAULT_MAP_FN : $this->firstNameKey;
        $this->lastNameKey = $oauthUtility->getStoreConfig(OAuthConstants::MAP_LASTNAME);
        $this->defaultRole = $oauthUtility->getStoreConfig(OAuthConstants::MAP_DEFAULT_ROLE);
        $this->checkIfMatchBy = $oauthUtility->getStoreConfig(OAuthConstants::MAP_MAP_BY);
        $this->userGroupModel = $userGroupModel;
        $this->adminRoleModel = $adminRoleModel;
        $this->adminUserModel = $adminUserModel;
        $this->customerModel = $customerModel;
        $this->storeManager = $storeManager;
        $this->checkIfMatchBy = $oauthUtility->getStoreConfig(OAuthConstants::MAP_MAP_BY);
        $this->responseFactory = $responseFactory;
        $this->customerLoginAction = $customerLoginAction;
        $this->customerFactory = $customerFactory;
        $this->userFactory = $userFactory;
        $this->randomUtility = $randomUtility;
            parent::__construct($context, $oauthUtility);
    }
    
    
    /**
     * Execute function to execute the classes function.
     *
     * @throws MissingAttributesException
     */
    public function execute()
    {
        // throw an exception if attributes are empty
        if (empty($this->attrs)) {
            throw new MissingAttributesException;
        }
        $firstName = array_key_exists($this->firstNameKey, $this->flattenedattrs) ?
            $this->flattenedattrs[$this->firstNameKey]: null;
        $lastName = array_key_exists($this->lastNameKey, $this->flattenedattrs) ? $this->flattenedattrs[$this->lastNameKey]: null;
        $userName = array_key_exists($this->usernameAttribute, $this->flattenedattrs) ? $this->flattenedattrs[$this->usernameAttribute]: null;
        if ($this->oauthUtility->isBlank($this->defaultRole)) {
            $this->defaultRole = OAuthConstants::DEFAULT_ROLE;
        }

        // process the user
        $this->processUserAction($this->userEmail, $firstName, $lastName, $userName, $this->defaultRole);
    }


    /**
     * This function processes the user values to either create
     * a new user on the site and log him/her in or log an existing
     * user to the site. Mapping is done based on $checkIfMatchBy
     * variable. Either email or username.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $checkIfMatchBy
     * @param $defaultRole
     */
    private function processUserAction($user_email, $firstName, $lastName, $userName, $defaultRole)
    {
        $admin = false;

        // check if the a customer or admin user exists based on the email in OAuth response
        $user = $this->getAdminUserFromAttributes($user_email);

        $admin = is_a($user, '\Magento\User\Model\User') ? true : false;

        if (!$user) {
            $user = $this->getCustomerFromAttributes($user_email);
        }

        $setDefaultRole = $this->processDefaultRole($admin, $defaultRole);
        // if no user found then create user
        if (!$user) {
            $user = $this->createNewUser($user_email, $firstName, $lastName, $userName, $user, $admin, $setDefaultRole);
        }

        $this->oauthUtility->log_debug("processUserAction: user created");
            //else $this->updateUserAttributes($firstName, $lastName, $setDefaultRole, $userName, $user, $admin);
        // log the user in to it's respective dashboard
        if ($admin) {
                    $this->redirectToBackendAndLogin($user->getId());
        } else {
            $this->oauthUtility->log_debug("processUserAction: redirecting customer");
            $this->customerLoginAction->setUser($user)->execute();
        }
    }


    /**
     * Function redirects the user to the backend with appropriate parameters
     * in the URL which will be read in the backend portion of the code
     * and log the admin in. We can't directly log the admin in from anywhere
     * in the code as Magento doesn't allow it.
     *
     * @param $userId
     * @return
     */
    private function redirectToBackendAndLogin($userId)
    {
        $this->oauthUtility->log_debug("processUserAction: redirectToBackendAndLogin");
        // set the admin query parameters to be passed on to the backend for processing
        $adminParams = ['option'=>OAuthConstants::LOGIN_ADMIN_OPT,'userid'=>$userId];
        // redirect the user to the backend
            $url = $this->oauthUtility->getAdminUrl('adminhtml', $adminParams);
//        $this->responseFactory->create()
//            ->setRedirect($url)
//            ->sendResponse();
//        exit;

            return $this->getResponse()->setRedirect($url)->sendResponse();
    }

    /**
     * Create a temporary email address based on the username
     * in the SAML response. Email Address is a required so we
     * need to generate a temp/fake email if no email comes from
     * the IDP in the SAML response.
     *
     * @param $userName
     * @return string
     */
    private function generateEmail($userName)
    {
        $this->oauthUtility->log_debug("processUserAction : generateEmail");
        $siteurl = $this->oauthUtility->getBaseUrl();
        $siteurl = substr($siteurl, strpos($siteurl, '//'), strlen($siteurl)-1);
        return $userName .'@'.$siteurl;
    }

    /**
     * Create a new user based on the SAML response and attributes. Log the user in
     * to it's appropriate dashboard. This class handles generating both admin and
     * customer users.
     *
     * @param $user_email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $defaultRole
     * @param $user
     */
    private function createNewUser($user_email, $firstName, $lastName, $userName, $user, &$admin, $defaultRole)
    {

        // generate random string to be inserted as a password
        $this->oauthUtility->log_debug("processUserAction: createNewUser");
        $random_password = $this->randomUtility->getRandomString(8);
        $userName = !$this->oauthUtility->isBlank($userName)? $userName : $user_email;
        $firstName = !$this->oauthUtility->isBlank($firstName) ? $firstName : $userName;
        $lastName = !$this->oauthUtility->isBlank($lastName) ? $lastName : $userName;

        // create admin or customer user based on the role
        $user = $admin ? $this->createAdminUser($userName, $user_email, $firstName, $lastName, $random_password, $defaultRole)
                        : $this->createCustomer($userName, $user_email, $firstName, $lastName, $random_password, $defaultRole);

        return $user;
    }

    /**
     * This function udpates the user attributes based on the value
     * in the SAML Response. This function decides if the user is
     * a customer or an admin and update it's attribute accordingly
     *
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $groupName
     * @param $defaultRole
     * @param $user
     */
    private function updateUserAttributes($firstName, $lastName, $defaultRole, $userName, $user, &$admin)
    {
        $this->oauthUtility->log_debug("processUserAction: updateUserAttributes");
        $userId = $user->getId();
        $admin = is_a($user, '\Magento\User\Model\User') ? true : false;

        // update the attributes
        if (!$this->oauthUtility->isBlank($firstName)) {
            $this->oauthUtility->saveConfig(OAuthConstants::DB_FIRSTNAME, $firstName, $userId, $admin);
        }
        if (!$this->oauthUtility->isBlank($lastName)) {
            $this->oauthUtility->saveConfig(OAuthConstants::DB_LASTNAME, $lastName, $userId, $admin);
        }
        if (!$this->oauthUtility->isBlank($userName)) {
            $this->oauthUtility->saveConfig(OAuthConstants::USER_NAME, $userName, $userId, $admin);
        }

        //update roles
        if (!$admin) {
            $user->setData('group_id', $defaultRole); // customer cannot have multiple groups
            $user->save();
        }
    }

    /**
     * Create a new customer.
     *
     * @param $email
     * @param $userName
     * @param $random_password
     * @param $role_assigned
     */
    private function createCustomer($userName, $email, $firstName, $lastName, $random_password, $role_assigned)
    {
        $this->oauthUtility->log_debug("processUserAction: createCustomer");
        $websiteId = $this->storeManager->getWebsite()->getWebsiteId();
        $store = $this->storeManager->getStore();
        $storeId = $store->getStoreId();
        $customer = $this->customerFactory->create()
                        ->setWebsiteId($websiteId)
                        ->setEmail($email)
                        ->setFirstname($firstName)
                        ->setLastname($lastName)
                        ->setPassword($random_password)
                        ->setGroupId($role_assigned)
                        ->save();

        return $customer;
    }


    /**
     * Create a New Admin User
     *
     * @param $email
     * @param $firstName
     * @param $lastName
     * @param $userName
     * @param $random_password
     * @param $role_assigned
     */
    private function createAdminUser($userName, $firstName, $lastName, $email, $random_password, $role_assigned)
    {
        $adminInfo = [
            'username'  => $userName,
            'firstname' => $firstName,
            'lastname'  => $lastName,
            'email'     => $email,
            'password'  => $random_password,
            'interface_locale' => 'en_US',
            'is_active' => 1
        ];

        $this->oauthUtility->log_debug("processUserAction: createAdminUser");

        $assign_role = empty($role_assigned) ? $role_assigned : 'Administrator';
        $user = $this->userFactory->create();
        $user->setData($adminInfo);
        $user->setRoleId($assign_role);
        $user->save();
        return $user;
    }


    /**
     * Get the Admin User from the Attributes in the SAML response.
     * Return False if the admin doesn't exist. The admin is fetched
     * by email or username based on the admin settings (checkifmatchby)
     *
     * @param $user_email
     */
    private function getAdminUserFromAttributes($user_email)
    {
        $adminUser = false;
        $this->oauthUtility->log_debug("processUserAction: getAdminUserFromAttributes");
        $connection = $this->adminUserModel->getResource()->getConnection();
        $select = $connection->select()->from($this->adminUserModel->getResource()->getMainTable())->where('email=:email');
        $binds = ['email' => $user_email];
        $adminUser = $connection->fetchRow($select, $binds);
        $adminUser = is_array($adminUser) ? $this->adminUserModel->loadByUsername($adminUser['username']) : $adminUser;
        return $adminUser;
    }

    /**
     * Process the default role and figure out if it's for
     * an admin or user. Return the ID of the default Role.
     *
     * @param $admin
     * @param $defaultRole
     */
    private function processDefaultRole($admin, $defaultRole)
    {
        if (is_null($defaultRole)) {
            return;
        }
        $this->oauthUtility->log_debug("processUserAction: processDefaultRole");
        $groups = $this->userGroupModel->toOptionArray();
        $roles = $this->adminRoleModel->toOptionArray();
        $setDefaultRole = "";


        if ($admin) {
            foreach ($roles as $role) { // admin roles
                $admin = $defaultRole==$role['label'] ? true : false;

                if ($admin) {
                    $setDefaultRole = $role['value'];
                      break;
                }
            }
        } else {
            foreach ($groups as $group) { // customer roles
                $admin = $defaultRole==$group['label']? false : true;
                if (!$admin) {
                    $setDefaultRole = $group['value'];
                    break;
                }
            }
        }

        return $setDefaultRole;
    }

    /**
     * Get the Customer User from the Attributes in the SAML response
     * Return false if the customer doesn't exist. The customer is fetched
     * by email only. There are no usernames to set for a Magento Customer.
     *
     * @param $user_email
     * @param $userName
     */
    private function getCustomerFromAttributes($user_email)
    {
        $this->oauthUtility->log_debug("processUserAction: getCustomerFromAttributes");
        $this->customerModel->setWebsiteId($this->storeManager->getStore()->getWebsiteId());
        $customer = $this->customerModel->loadByEmail($user_email);
        return !is_null($customer->getId()) ? $customer : false;
    }


    /** The setter function for the Attributes Parameter */
    public function setAttrs($attrs)
    {
        $this->attrs = $attrs;
        return $this;
    }

    /** The setter function for the Attributes Parameter */
    public function setFlattenedAttrs($flattenedattrs)
    {
        $this->flattenedattrs = $flattenedattrs;
        return $this;
    }

    /** Setter for the User Email Parameter */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
        return $this;
    }
}
