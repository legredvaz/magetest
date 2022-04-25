<?php
namespace HKGDealers\PDFInvoice\Observer;
 
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Customer\Model\Session;
 
class OrderObserver implements ObserverInterface
{

  protected $_curl;

  protected $_logger;

  private $scopeConfig;

  protected $customerSession;

  public function __construct(
   \Magento\Framework\HTTP\Client\Curl $curl,
   LoggerInterface $logger,
   ScopeConfigInterface $scopeConfig,
   Session $customerSession
) {
   $this->_curl = $curl;
   $this->_logger = $logger;
   $this->scopeConfig = $scopeConfig;
   $this->customerSession = $customerSession;
}

  public function execute(\Magento\Framework\Event\Observer $observer)
    {
      try {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        if($this->customerSession->getOrderSyncing() && $this->customerSession->getOrderSyncing() == "create")
        {
          $this->customerSession->unsOrderSyncing();
          $objectManager->get(\Psr\Log\LoggerInterface::class)->info("returning", array());
          return;
        }
          
        $objectManager->get(\Psr\Log\LoggerInterface::class)->info("going inside", array());
        $order = $observer->getEvent()->getOrder();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $helper = $objectManager->get('\HKGDealers\AdvancePayment\Helper\Data');
        $secondOrder = $helper->createMageOrder($order);
        $objectManager->get(\Psr\Log\LoggerInterface::class)->info("got second order", array());
        if($secondOrder) {
          try {
             $order->setChildOrder($secondOrder->getIncrementId());
             $objectManager->get(\Psr\Log\LoggerInterface::class)->info("set child order", array());
             $order->save();
             $objectManager->get(\Psr\Log\LoggerInterface::class)->info("order saved", array());
             }
       catch (\Exception $e1) {
          //$context = array();
            //$this->_logger->info($e->getMessage(), $context); 
        }
           }
        $products = [];
        $totalOrderAmount = $order->getGrandTotal();
        if($secondOrder) {
          $products = $this->getOrderProducts($order,$secondOrder);
          $totalOrderAmount = $totalOrderAmount + $secondOrder->getGrandTotal();
          }
       else {
        $products = $this->getOrderProducts($order);
       }
             //$orderId = $order->getEntityId();
      $orderIncrementedId = $order->getIncrementId();
      $customerEmail = $order->getCustomerEmail();
      $customerId = $order->getCustomerId();
      $domain = $order->getStore()->getWebsite()->getName();
      $transactionId = $order->getPayment()->getLastTransId();
      $orderKey = "Magento";
      $creditAmount = $order->getGrandTotal();
            // if(!empty($order->getGrandTotal()) && is_numeric($order->getGrandTotal())) 
            //     $creditAmount = (30 * $order->getGrandTotal())/100.0; 

       $url = $this->scopeConfig->getValue('hkgmain/hkg_general/order_sync', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
      //$url = "https://y8ljlmtkt8.execute-api.us-east-1.amazonaws.com/prod/";
      if(!empty($url))  { 
         $params = [
        'user_email' => $customerEmail,
        'user_id' => $customerId,
        'store_id' => $order->getStore()->getWebsiteId(),
        'order_id' => $orderIncrementedId,
        'order_key' => $orderKey,
        'domain' => $domain,
        'transaction_id' => $transactionId,
        'order_platform' => "magento",
        'product' => $products,
        'total_order_amount' => $totalOrderAmount,
        'credit_amount' => $creditAmount,
        'percent_amount' => 30
          ];

          $context = array();
          $jsoneObject = $objectManager->create('Magento\Framework\Serialize\Serializer\Json');
          $jsonString = $jsoneObject->serialize($params);
          //$this->_logger->info("*****params*******", $context);
          //$this->_logger->info($jsonString, $context);

         $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
         $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
         $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
         $this->_curl->setOption(CURLOPT_TIMEOUT, 60);
         $this->_curl->addHeader("Content-Type", "application/json");
         $this->_curl->post($url, $jsonString);
         $response = $this->_curl->getBody();
         $objectManager->get(\Psr\Log\LoggerInterface::class)->info("order sync status : ".$response, array());
       }    
     }
       catch (\Exception $e) {
          $context = array();
            $this->_logger->info($e->getMessage(), $context); 
        }
 
    }


    public function getOrderProducts($order,$secondOrder = NULL)
    {
      $items = [];
      try {
         foreach ($order->getAllItems() as $item) {
            $productData = array('sku' => $item->getSku(), 'product_count' => $item->getQtyOrdered(), 'single_product_amount' => $item->getPrice());
           $items[] =  $productData;
         }

       if ($secondOrder) {
        foreach ($secondOrder->getAllItems() as $item) {
          foreach ($items as $key =>  $olditem) {
            if($olditem['sku'] == $item->getSku()) {
              $items[$key]['product_count'] = $olditem['product_count'] + $item->getQtyOrdered();
            }
         }
       }  

       }
     }
       catch (\Exception $e) {
            
        }
        return $items;
    }

   

 /* public function getOrderDetails($order)
  {
    
   

   try{
      $orderId =  $order->getId();
      $email = $order->getCustomerEmail();
      $customerId = $order->getCustomerId();
      $website = $order->getStore()->getWebsite()->getName(); 
     // $writer = new \Zend\Log\Writer\Stream(BP . '/var/log/test.log');
     // $logger = new \Zend\Log\Logger();
      //$logger->addWriter($writer);
      //$logger->info('order Id : '.print_r($order,true));
      print_r($order);
   }
   catch(\Exception $e)
   {

   }
  }
*/
}