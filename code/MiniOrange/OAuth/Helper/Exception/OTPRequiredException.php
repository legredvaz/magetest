<?php

namespace MiniOrange\OAuth\Helper\Exception;

use MiniOrange\OAuth\Helper\OAuthMessages;

/**
 * Exception denotes that user has not entered
 * OTP for validation.
 */
class OTPRequiredException extends \Exception
{
    public function __construct()
    {
        $message     = OAuthMessages::parse('REQUIRED_OTP');
        $code         = 113;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
