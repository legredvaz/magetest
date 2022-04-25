<?php

namespace MiniOrange\OAuth\Helper;

/** This class lists down constant values used all over our Module. */
class OAuthConstants
{
    const MODULE_DIR         = 'MiniOrange_OAuth';
    const MODULE_TITLE         = 'OAuth Client';

    //ACL Settings
    const MODULE_BASE         = '::OAuth';
    const MODULE_OAUTHSETTINGS = '::oauth_settings';
    const MODULE_SIGNIN     = '::signin_settings';
    const MODULE_ATTR          = '::attr_settings';
    const MODULE_FAQ          = '::faq_settings';
    const MODULE_ACCOUNT    = '::account_settings';
    const MODULE_SUPPORT    = '::support';
    const MODULE_UPGRADE     = '::upgrade';

    const MODULE_IMAGES     = '::images/';
    const MODULE_CERTS         = '::certs/';
    const MODULE_CSS         = '::css/';
    const MODULE_JS         = '::js/';

    // request option parameter values
    const LOGIN_ADMIN_OPT    = 'oauthLoginAdminUser';
    const TEST_CONFIG_OPT     = 'testConfig';

    //database keys
    
    const APP_NAME          = 'appName';
    const CLIENT_ID         = 'clientID';
    const CLIENT_SECRET     = 'clientSecret';
    const SCOPE             = 'scope';
    const AUTHORIZE_URL     = 'authorizeURL';
    const ACCESSTOKEN_URL   = 'accessTokenURL';
    const GETUSERINFO_URL   = 'getUserInfoURL';
    const OAUTH_LOGOUT_URL  = 'oauthLogoutURL';
    const TEST_RELAYSTATE     = 'testvalidate';
    const MAP_MAP_BY         = 'amAccountMatcher';
    const DEFAULT_MAP_BY     = 'email';
    const DEFAULT_GROUP     = 'General';
    const SEND_HEADER   =   'header';
    const SEND_BODY    = 'body';

    const NAME_ID             = 'nameId';
    const IDP_NAME             = 'identityProviderName';
    const X509CERT             = 'certificate';
    const RESPONSE_SIGNED     = 'responseSigned';
    const ASSERTION_SIGNED     = 'assertionSigned';
    const ISSUER             = 'samlIssuer';
    const DB_FIRSTNAME         = 'firstname';
    const USER_NAME         = 'username';
    const DB_LASTNAME         = 'lastname';
    const CUSTOMER_KEY         = 'customerKey';
    const CUSTOMER_EMAIL    = 'email';
    const CUSTOMER_PHONE    = 'phone';
    const CUSTOMER_NAME        = 'cname';
    const CUSTOMER_FNAME    = 'customerFirstName';
    const CUSTOMER_LNAME    = 'customerLastName';
    const SAMLSP_CKL         = 'ckl';
    const SAMLSP_LK         = 'lk';
    const SHOW_ADMIN_LINK     = 'showadminlink';
    const SHOW_CUSTOMER_LINK= 'showcustomerlink';
    const REG_STATUS         = 'registrationStatus';
    const API_KEY             = 'apiKey';
    const TOKEN             = 'token';
    const BUTTON_TEXT         = 'buttonText';
    const OTP_TYPE             = 'otpType';
    const IS_TEST           = 'isTest';

    // attribute mapping constants
    const MAP_EMAIL         = 'amEmail';
    const DEFAULT_MAP_EMAIL = 'email';
    const MAP_USERNAME        = 'amUsername';
    const DEFAULT_MAP_USERN = 'username';
    const MAP_FIRSTNAME     = 'amFirstName';
    const DEFAULT_MAP_FN     = 'firstName';
    const MAP_LASTNAME         = 'amLastName';
    const MAP_DEFAULT_ROLE     = 'defaultRole';
    const DEFAULT_ROLE         = 'General';
    const MAP_GROUP         = 'group';
    const UNLISTED_ROLE     = 'unlistedRole';
    const CREATEIFNOTMAP     = 'createUserIfRoleNotMapped';

    //URLs
    const OAUTH_LOGIN_URL     = 'mooauth/actions/sendAuthorizationRequest';

    //images
    const IMAGE_RIGHT         = 'right.png';
    const IMAGE_WRONG         = 'wrong.png';

    const TXT_ID             = 'miniorange/oauth/transactionID';
    const CALLBACK_URL      = 'mooauth/actions/ReadAuthorizationResponse';
    const CODE              = 'code';
    const GRANT_TYPE        = 'authorization_code';

    //OAUTH Constants
    const OAUTH              = 'OAUTH';
    const HTTP_REDIRECT     = 'HttpRedirect';

    //OTP Constants
    const OTP_TYPE_EMAIL    = 'email';
    const OTP_TYPE_PHONE    = 'sms';

    //Registration Status
    const STATUS_VERIFY_LOGIN     = "MO_VERIFY_CUSTOMER";
    const STATUS_COMPLETE_LOGIN = "MO_VERIFIED";
    const STATUS_VERIFY_EMAIL     = "MO_OTP_EMAIL_VALIDATE";

    //plugin constants
    const DEFAULT_CUSTOMER_KEY     = "16555";
    const DEFAULT_API_KEY         = "fFd2XcvTGDemZvbw1bcUesNJWEqKbbUq";
    //const DEFAULT_CUSTOMER_KEY     = "16672";
    //const DEFAULT_API_KEY         = "F3fqktYvqo2oApdduYNMTkrYRrlPdnpW";
    const HOSTNAME                = "https://login.xecurify.com";
    const AREA_OF_INTEREST         = 'Magento 2.0 OAuth Client Plugin';
}
