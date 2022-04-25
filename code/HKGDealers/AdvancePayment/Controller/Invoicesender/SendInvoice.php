<?php

namespace HKGDealers\AdvancePayment\Controller\Invoicesender;

 use Magento\Sales\Model\Order\Invoice;
 use Magento\Sales\Model\Order\Email\Sender\InvoiceSender as SalesInvoiceSender;
 use Magento\Framework\Controller\ResultFactory;
 use Magento\Framework\App\Config\ScopeConfigInterface;


class SendInvoice extends \Magento\Framework\App\Action\Action
{

    protected $salesInvoiceSender;

    protected $invoiceModel;

    private $scopeConfig;

    protected $_curl;

	public function __construct(\Magento\Framework\App\Action\Context $context, Invoice $invoiceModel, SalesInvoiceSender $salesInvoiceSender, \Magento\Framework\HTTP\Client\Curl $curl, ScopeConfigInterface $scopeConfig)
	{
		parent::__construct($context);
        $this->salesInvoiceSender = $salesInvoiceSender;
        $this->invoiceModel = $invoiceModel;
        $this->_curl = $curl;
        $this->scopeConfig = $scopeConfig;
	}

	public function execute()
	{
     $invoiceId =  $this->getRequest()->getParam('id');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $invoice =  $this->invoiceModel->loadByIncrementId($invoiceId);
            if(empty($invoice->getEmailSent())) {
            $this->salesInvoiceSender->send($invoice);

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
             $url = $this->scopeConfig->getValue('hkgmain/hkg_general/invoice_log', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if(!empty($url)) {
            $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
           $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
           $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
           $this->_curl->setOption(CURLOPT_TIMEOUT, 60);
           $this->_curl->addHeader("Content-Type", "application/json");
           $this->_curl->post($url, $jsonString);
           $response1 = $this->_curl->getBody(); 
       }
    }
           $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
           $resultRedirect->setUrl('/thank-you');
           return $resultRedirect;

        } catch(\Exception $e)
        {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), array()); 
        }
        
	}
}