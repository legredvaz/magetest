<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\PDFInvoice\Plugin\Customer\Model;
use Magento\Customer\Model\Registration;
/**
 * @api
 * @since 100.0.2
 */
class DisableRegistration
{
    /**
     * Check whether customers registration is allowed
     *
     * @return bool
     */
    public function afterIsAllowed(Registration $subject, $result)
    {
        return false;
    }
}
