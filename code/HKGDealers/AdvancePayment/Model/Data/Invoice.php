<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HKGDealers\AdvancePayment\Model\Data;

/**
 * Invoice data model
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Invoice extends \Magento\Framework\DataObject implements
    \HKGDealers\AdvancePayment\Api\Data\InvoiceInterface
{

   
     
    /**
     * @inheritdoc
     */
    public function setSku($sku)
    {
    return $this->setData(self::SKU, $sku);
    }

    /**
     * @inheritdoc
     */
    public function getSku()
    {
        return $this->_getData(self::SKU);
    }

     /**
     * @inheritdoc
     */
    public function setQtyToInvoice($qtyToInvoice)
    {
    return $this->setData(self::QTYTOINVOICE, $qtyToInvoice);
    }

    /**
     * @inheritdoc
     */
    public function getQtyToInvoice()
    {
        return $this->_getData(self::QTYTOINVOICE);
    }

}