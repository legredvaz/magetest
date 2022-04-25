<?php
 namespace HKGDealers\AdvancePayment\Model\Order;

 use HKGDealers\AdvancePayment\Api\Data\InvoiceInterface;
 use HKGDealers\AdvancePayment\Api\InvoiceRepositoryInterface;
 use Magento\Sales\Model\Service\InvoiceService;
 use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
 use Magento\Sales\Model\Order\Invoice;
 use HKGDealers\AdvancePayment\Helper\MailSender;

 class InvoiceRepository implements InvoiceRepositoryInterface {

    protected $invoiceService;

    protected $transaction;

    protected $invoiceSender;

    protected $invoiceModel;

    protected $emailHelper;

    protected $storeManager;

    protected $_curl;

    public function __construct(InvoiceService $invoiceService,\Magento\Framework\DB\Transaction $transaction, InvoiceSender $invoiceSender, Invoice $invoiceModel, MailSender $emailHelper, \Magento\Store\Model\StoreManagerInterface $storeManager, \Magento\Framework\HTTP\Client\Curl $curl) {
        $this->invoiceService = $invoiceService;
        $this->transaction = $transaction;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceModel = $invoiceModel;
        $this->emailHelper = $emailHelper;
        $this->storeManager = $storeManager;
        $this->_curl = $curl;

    }

     /**
     * @inheritdoc
     */

 	public function save($orderId,$used_credit_amount,$balance_credit_amount,$resample,$retest,array $products) {

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $context = array();
        $response = ['success' => false, 'message' => ""];
         try {
            $order = $objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($orderId);
            if (!$order->getId()) {
                $response['message'] = "The order no longer exists.";
                return json_encode($response);
            }

             $order->setUsedCreditAmount($used_credit_amount);
             $order->setBalanceCreditAmount($balance_credit_amount);
             $order->save();

             $canOrderInvoice = false;

             if(empty($order->getChildOrder()) && $order->canInvoice()) {
                    $canOrderInvoice = true;
                }

            if($balance_credit_amount == 0 || $canOrderInvoice == true ) {

                if(!empty($order->getChildOrder())) {
                    $order = $objectManager->create(\Magento\Sales\Model\Order::class)->loadByIncrementId($order->getChildOrder());
                }

                if (!$order->getId()) {
                $response['message'] = "The order no longer exists.";
                return json_encode($response);
                }
                
                if (!$order->canInvoice()) {
                $response['message'] = "The order does not allow an invoice to be created.";
                return json_encode($response);
                }

                if ($order->getId()) {
                    $stringId = 0;
                    $skuQty = array();
                    foreach($products as $product)
                    {
                        $skuQty[$product->getSku()] = $product->getQtyToInvoice();
                    }
                    $itemQty = array();
                    foreach ($order->getAllItems() as $orderItem) {
                        if(array_key_exists($orderItem->getSku(), $skuQty))
                        {
                             $itemQty[$orderItem->getId()] = $skuQty[$orderItem->getSku()];
                        }
                       
                     }

                     $invoice = $this->invoiceService->prepareInvoice($order, $itemQty);

                     if (!$invoice) {
                        $response['message'] = "The invoice can't be saved at this time. Please try again later.";
                        return json_encode($response);
                     }

                     if (!$invoice->getTotalQty()) {
                        $response['message'] = "The invoice can't be created without products. Add products and try again.";
                        return json_encode($response);
                      }

                      $invoice->register();
                      //$invoice->save();
                $transactionSave = $this->transaction->addObject($invoice)->addObject($invoice->getOrder());
                $transactionSave->save();
                $url = "http://18.116.67.222/default/advancepayment/Invoicesender/SendInvoice/id/".$invoice->getIncrementId();
                $this->emailHelper->sendMail($invoice,$url,$resample,$retest);
                $response['success'] = true;
                $response['message'] = "Invoice Generated successfully";
                return json_encode($response);
                }

            }
            
        } catch(\Exception $e)
        {
          $objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), $context); 
        }

        $response = ['success' => false];
        return json_encode($response);

     /*   $response = ['success' => false ];
        $string = "";
        foreach ($products as $value) {
               $string = $value->getSku();
            }
        try {
             $response = ['success' => true];
        return json_encode($response);

        }
        catch(\Exception $e)
        {

        } */
       
    }

    /**
     * @inheritdoc
     */
    public function sendInvoice($id) {
        $response = ['success' => false, 'message' => ""];
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $invoice =  $this->invoiceModel->loadByIncrementId($id);
            $this->invoiceSender->send($invoice);
            $response['success'] = true;
            $response['message'] = "Invoice sent sucessfully";

            $pk = "SITE_ORDER_SITE_ID#".$invoice->getOrder()->getIncrementId()."#SITE#".$invoice->getOrder()->getStore()->getWebsiteId();
            $sk = "USERID#".$invoice->getOrder()->getCustomerId()."#TS#".strtotime(date("Y/m/d H:i:s"));
            $params = [
              'pk' => $pk,
              'sk' => $sk,
              'eventtype' => "SITE_ORDER_INVOICE_LOG",
              'status' => "",
              'type' => "event",
              'result' => "SITE_ORDER_INVOICE_ACCEPTED"
                ];
        $jsoneObject = $objectManager->create('Magento\Framework\Serialize\Serializer\Json');
        $jsonString = $jsoneObject->serialize($params);
            $url ="https://mhvxo4w0cg.execute-api.us-east-1.amazonaws.com/prod/";
            $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
            $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
           $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
           $this->_curl->setOption(CURLOPT_TIMEOUT, 60);
           $this->_curl->addHeader("Content-Type", "application/json");
           $this->_curl->post($url, $jsonString);
           $response1 = $this->_curl->getBody();
            return json_encode($response);

        } catch(\Exception $e)
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), array()); 
        }
        $response['message'] = "Failed to send Invoice";
        return json_encode($response);
        
    }
 
 }


?>