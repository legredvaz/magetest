<?php

namespace MiniOrange\OAuth\Helper;

use MiniOrange\OAuth\Helper\OAuthConstants;

/**
 * This class denotes all the cURL related functions.
 */
class Curl
{

    public static function create_customer($email, $company, $password, $phone = '', $first_name = '', $last_name = '')
    {
        $url = OAuthConstants::HOSTNAME . '/moas/rest/customer/add';
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'companyName' => $company,
            'areaOfInterest' => OAuthConstants::AREA_OF_INTEREST,
            'firstname' => $first_name,
            'lastname' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'password' => $password
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function get_customer_key($email, $password)
    {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/key";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'email' => $email,
            'password' => $password
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function check_customer($email)
    {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/check-if-exists";
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'email' => $email,
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function mo_send_otp_token($auth_type, $email = '', $phone = '')
    {
        $url = OAuthConstants::HOSTNAME . '/moas/api/auth/challenge';
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'customerKey' => $customerKey,
            'email' => $email,
            'phone' => $phone,
            'authType' => $auth_type,
            'transactionName' => OAuthConstants::AREA_OF_INTEREST
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function mo_send_access_token_request($postData, $url, $clientID, $clientSecret)
    {
        $authHeader = [
            "Content-Type: application/x-www-form-urlencoded",
            'Authorization: Basic '.base64_encode($clientID.":".$clientSecret)
        ];
        $response = self::callAPI($url, $postData, $authHeader);
        return $response;
    }

    public static function mo_send_user_info_request($url, $headers)
    {

        $response = self::callAPI($url, [], $headers);
        return $response;
    }


    public static function validate_otp_token($transactionId, $otpToken)
    {
        $url = OAuthConstants::HOSTNAME . '/moas/api/auth/validate';
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;
        $fields = [
            'txId' => $transactionId,
            'token' => $otpToken,
        ];
        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    public static function submit_contact_us(
        $q_email,
        $q_phone,
        $query,
        $first_name,
        $last_name,
        $companyName
    ) {
        $url = OAuthConstants::HOSTNAME . "/moas/rest/customer/contact-us";
        $query = '[' . OAuthConstants::AREA_OF_INTEREST . ']: ' . $query;
        $customerKey = OAuthConstants::DEFAULT_CUSTOMER_KEY;
        $apiKey = OAuthConstants::DEFAULT_API_KEY;

        $fields = [
            'firstName' => $first_name,
            'lastName' => $last_name,
            'company' => $companyName,
            'email' => $q_email,
            'phone' => $q_phone,
            'query' => $query,
                        'ccEmail' => 'magentosupport@xecurify.com'
                ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);


        return true;
    }

    public static function forgot_password($email, $customerKey, $apiKey)
    {
        $url = OAuthConstants::HOSTNAME . '/moas/rest/customer/password-reset';

        $fields = [
            'email' => $email
        ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }


    public static function check_customer_ln($customerKey, $apiKey)
    {
        $url = OAuthConstants::HOSTNAME . '/moas/rest/customer/license';
        $fields = [
            'customerId' => $customerKey,
            'applicationName' => OAuthConstants::APPLICATION_NAME,
            'licenseType' => !MoUtility::micr() ? 'DEMO' : 'PREMIUM',
        ];

        $authHeader = self::createAuthHeader($customerKey, $apiKey);
        $response = self::callAPI($url, $fields, $authHeader);
        return $response;
    }

    private static function createAuthHeader($customerKey, $apiKey)
    {
        $currentTimestampInMillis = round(microtime(true) * 1000);
        $currentTimestampInMillis = number_format($currentTimestampInMillis, 0, '', '');

        $stringToHash = $customerKey . $currentTimestampInMillis . $apiKey;
        $authHeader = hash("sha512", $stringToHash);

        $header = [
            "Content-Type: application/json",
            "Customer-Key: $customerKey",
            "Timestamp: $currentTimestampInMillis",
            "Authorization: $authHeader"
        ];
        return $header;
    }

    private static function callAPI($url, $jsonData = [], $headers = ["Content-Type: application/json"])
    {
        // Custom functionality written to be in tune with Mangento2 coding standards.
        $curl = new MoCurl();
        $options = [
            'CURLOPT_FOLLOWLOCATION' => true,
            'CURLOPT_ENCODING' => "",
            'CURLOPT_RETURNTRANSFER' => true,
            'CURLOPT_AUTOREFERER' => true,
            'CURLOPT_TIMEOUT' => 0,
            'CURLOPT_MAXREDIRS' => 10
        ];


        $data = in_array("Content-Type: application/x-www-form-urlencoded", $headers)
            ? (!empty($jsonData) ? http_build_query($jsonData) : "") : (!empty($jsonData) ? json_encode($jsonData) : "");

        $method = !empty($data) ? 'POST' : 'GET';
        $curl->setConfig($options);
        $curl->write($method, $url, '1.1', $headers, $data);
        $content = $curl->read();
        $curl->close();
        return $content;
    }
}
