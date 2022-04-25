<?php

namespace MiniOrange\OAuth\Controller\Actions;

use MiniOrange\OAuth\Helper\Exception\MissingAttributesException;
use MiniOrange\OAuth\Helper\OAuthConstants;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * This class handles checking of the SAML attributes and NameID
 * coming in the response and mapping it to the attribute mapping
 * done in the plugin settings by the admin to update the user.
 */
class CheckAttributeMappingAction extends BaseAction implements HttpPostActionInterface
{
    const TEST_VALIDATE_RELAYSTATE = OAuthConstants::TEST_RELAYSTATE;

    private $userInfoResponse;
    private $flattenedUserInfoResponse;
    private $relayState;
    private $userEmail;

    private $emailAttribute;
    private $usernameAttribute;
    private $firstName;
    private $lastName;
    private $checkIfMatchBy;
    private $groupName;

    private $testAction;
    private $processUserAction;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \MiniOrange\OAuth\Helper\OAuthUtility $oauthUtility,
        \MiniOrange\OAuth\Controller\Actions\ShowTestResultsAction $testAction,
        \MiniOrange\OAuth\Controller\Actions\ProcessUserAction $processUserAction
    ) {
        //You can use dependency injection to get any class this observer may need.
        $this->emailAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_EMAIL);
        $this->emailAttribute = $oauthUtility->isBlank($this->emailAttribute) ? OAuthConstants::DEFAULT_MAP_EMAIL : $this->emailAttribute;
        $this->usernameAttribute = $oauthUtility->getStoreConfig(OAuthConstants::MAP_USERNAME);
        $this->usernameAttribute = $oauthUtility->isBlank($this->usernameAttribute) ? OAuthConstants::DEFAULT_MAP_USERN : $this->usernameAttribute;
        $this->firstName = $oauthUtility->getStoreConfig(OAuthConstants::MAP_FIRSTNAME);
        $this->firstName = $oauthUtility->isBlank($this->firstName) ? OAuthConstants::DEFAULT_MAP_FN : $this->firstName;
        $this->lastName = $oauthUtility->getStoreConfig(OAuthConstants::MAP_LASTNAME);
        $this->checkIfMatchBy = $oauthUtility->getStoreConfig(OAuthConstants::MAP_MAP_BY);
        $this->testAction = $testAction;
        $this->processUserAction = $processUserAction;
        parent::__construct($context, $oauthUtility);
    }

    /**
     * Execute function to execute the classes function.
     */
    public function execute()
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: execute");
        $attrs = $this->userInfoResponse;
        $flattenedAttrs =  $this->flattenedUserInfoResponse;
        $userEmail = $this->userEmail;

        $this->moOAuthCheckMapping($attrs, $flattenedAttrs, $userEmail);
    }
    

    /**
     * This function checks the SAML Attribute Mapping done
     * in the plugin and matches it to update the user's
     * attributes.
     *
     * @param $attrs
     * @throws MissingAttributesException;
     */
    private function moOAuthCheckMapping($attrs, $flattenedAttrs, $userEmail)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: moOAuthCheckMapping");
        if (empty($attrs)) {
            throw new MissingAttributesException;
        }

        $this->checkIfMatchBy = OAuthConstants::DEFAULT_MAP_BY;
        $this->processFirstName($flattenedAttrs);
        $this->processLastName($flattenedAttrs);
        $this->processUserName($flattenedAttrs);
        $this->processEmail($flattenedAttrs);
        $this->processGroupName($flattenedAttrs);

        $this->processResult($attrs, $flattenedAttrs, $userEmail);
    }


    /**
     * Process the result to either show a Test result
     * screen or log/create user in Magento.
     *
     * @param $attrs
     */
    private function processResult($attrs, $flattenedattrs, $email)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: processResult");
        $isTest =  $this->oauthUtility->getStoreConfig(OAuthConstants::IS_TEST);

        if ($isTest == true) {
            $this->oauthUtility->setStoreConfig(OAuthConstants::IS_TEST, false);
            $this->oauthUtility->flushCache();
            $this->testAction->setAttrs($flattenedattrs)->setUserEmail($email)->execute();
        } else {
            $this->processUserAction->setFlattenedAttrs($flattenedattrs)->setAttrs($attrs)->setUserEmail($email)->execute();
        }
    }

    /**
     * Check if the attribute list has a FirstName. If
     * no firstName is found then NameID is considered as
     * the firstName. This is done because Magento needs
     * a firstName for creating a new user.
     *
     * @param $attrs
     */
    private function processFirstName(&$attrs)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: processFirstName");
        if (!array_key_exists($this->firstName, $attrs)) {
            $parts  = explode("@", $this->userEmail);
            $name = $parts[0];
            $this->oauthUtility->log_debug("CheckAttributeMappingAction: processFirstName: ".$name);
            $attrs[$this->firstName] = $name;
        }
    }

    private function processLastName(&$attrs)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: processLastName");
        if (!array_key_exists($this->lastName, $attrs)) {
            $parts  = explode("@", $this->userEmail);
            $name = $parts[1];
            $this->oauthUtility->log_debug("CheckAttributeMappingAction: processLastName: ".$name);
            $attrs[$this->lastName] = $name;
        }
    }


    /**
     * Check if the attribute list has a UserName. If
     * no UserName is found then NameID is considered as
     * the UserName. This is done because Magento needs
     * a UserName for creating a new user.
     *
     * @param $attrs
     */
    private function processUserName(&$attrs)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: procesUserName");
        if (!array_key_exists($this->usernameAttribute, $attrs)) {
            $attrs[$this->usernameAttribute] = $this->userEmail;
        }
    }


    /**
     * Check if the attribute list has a Email. If
     * no Email is found then NameID is considered as
     * the Email. This is done because Magento needs
     * a Email for creating a new user.
     *
     * @param $attrs
     */
    private function processEmail(&$attrs)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: processEmail");
        if (!array_key_exists($this->emailAttribute, $attrs)) {
            $attrs[$this->emailAttribute] = $this->userEmail;
        }
    }


    /**
     * Check if the attribute list has a Group/Role. If
     * no Group/Role is found then NameID is considered as
     * the Group/Role. This is done because Magento needs
     * a Group/Role for creating a new user.
     *
     * @param $attrs
     */
    private function processGroupName(&$attrs)
    {
        $this->oauthUtility->log_debug("CheckAttributeMappingAction: processGroupName");
        if (!array_key_exists($this->groupName, $attrs)) {
            $this->groupName = [];
        }
    }


    /** Setter for the OAuth Response Parameter */
    public function setUserInfoResponse($userInfoResponse)
    {
        $this->userInfoResponse = $userInfoResponse;
        return $this;
    }

    /** Setter for the OAuth Response Parameter */
    public function setFlattenedUserInfoResponse($flattenedUserInfoResponse)
    {
        $this->flattenedUserInfoResponse = $flattenedUserInfoResponse;
        return $this;
    }

    /** Setter for the user email Parameter */
    public function setUserEmail($userEmail)
    {
        $this->userEmail = $userEmail;
        return $this;
    }

    /** Setter for the RelayState Parameter */
    public function setRelayState($relayState)
    {
        $this->relayState = $relayState;
        return $this;
    }
}
