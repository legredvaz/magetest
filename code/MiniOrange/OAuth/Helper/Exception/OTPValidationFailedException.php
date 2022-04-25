<?php

namespace MiniOrange\OAuth\Helper\Exception;

use MiniOrange\OAuth\Helper\OAuthMessages;

/**
 * Exception denotes that user didnot pass the OTP
 * validation.
 */
class OTPValidationFailedException extends \Exception
{
    public function __construct()
    {
        $message     = OAuthMessages::parse('INVALID_OTP');
        $code         = 114;
        parent::__construct($message, $code, null);
    }

    public function __toString()
    {
        return __CLASS__ . ": [{$this->code}]: {$this->message}\n";
    }
}
