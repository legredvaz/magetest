<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace HKGDealers\AdvancePayment\Api\Data;


interface InvoiceInterface
{
     const SKU = 'sku';

     const QTYTOINVOICE = 'qtyToInvoice';

    /**
     * Set SKU
     *
     * @param string $sku
     * @return \HKGDealers\AdvancePayment\Api\Data\InvoiceInterface
     */
    public function setSku($sku);

    /**
     * Get SKU
     *
     * @return string
     */
    public function getSku();

    /**
     * Set Quantity to Invoice
     *
     * @param int $qtyToInvoice
     * @return \HKGDealers\AdvancePayment\Api\Data\InvoiceInterface
     */
    public function setQtyToInvoice($qtyToInvoice);

    /**
     * Get Quantity to Invoice
     *
     * @return int
     */
    public function getQtyToInvoice();

}