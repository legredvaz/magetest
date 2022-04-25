<?php
 namespace HKGDealers\AdvancePayment\Api;

 interface InvoiceRepositoryInterface {

    /**
     * @param string $orderId
     * @param float $used_credit_amount
     * @param float $balance_credit_amount
     * @param int $resample
     * @param int $retest
     * @param \HKGDealers\AdvancePayment\Api\Data\InvoiceInterface[] $products
     * @return string $message
     */
 	public function save($orderId,$used_credit_amount,$balance_credit_amount,$resample,$retest,array $products);

    /**
     * sends specific order invoice.
     *
     * @param string $id
     * @return string $message
     */
   public function sendInvoice($id);
 }


?>