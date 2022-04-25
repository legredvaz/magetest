<?php
namespace HKGDealers\AdvancePayment\Controller\Adminhtml\OldOrders;
use Magento\Backend\App\Action;
use Magento\Framework\App\Config\ScopeConfigInterface;

class Save extends \Magento\Backend\App\Action
{

  protected $forwardFactory;

  protected $orderModel;

  private $scopeConfig;

  protected $_curl;

  public function __construct( Action\Context $context, \Magento\Sales\Model\Order $orderModel, \Magento\Framework\HTTP\Client\Curl $curl,  ScopeConfigInterface $scopeConfig)
  {
    $this->orderModel = $orderModel;
    $this->_curl = $curl;
    $this->scopeConfig = $scopeConfig;
    parent::__construct($context);
  }

  protected function _isAllowed()
  {
    return $this->_authorization->isAllowed("HKGDealers_AdvancePayment::parent");
  }

    public function execute()
    {

      $data = $this->getRequest()->getPostValue();
      $resultRedirect = $this->resultRedirectFactory->create(); 
      $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
      try {

      if($data){
        $order_reports = $this->getRequest()->getParam('order_reports');
        $objectManager->get(\Psr\Log\LoggerInterface::class)->info("Entered In second order", $order_reports);
        if(array_key_exists("order_id",$order_reports) && !empty($order_reports['order_id'])) {
           $orderId = $order_reports["order_id"];
           $this->orderModel->loadByIncrementId($orderId);
           if (!$this->orderModel->getId()) {
                $this->messageManager->addErrorMessage(__("The order no longer exists."));
                return $resultRedirect->setPath('*/*/index');
                }
          if(!empty($this->orderModel->getCompletedSamples()) || !empty($this->orderModel->getRetestedSamples()) ||!empty($this->orderModel->getResampledSamples()) ) {
             $this->messageManager->addErrorMessage(__("Reports can be submitted only once"));
                return $resultRedirect->setPath('*/*/index');
          }
          $orderItemPrice = $this->getPrice();

           $creditAmountRemaining =  intval($order_reports['credit_amount_paid']) - (intval($order_reports['complete']) * $orderItemPrice);
           if($creditAmountRemaining < 0) {
            $creditAmountRemaining = 0;
           }
            
           $this->orderModel->setBalanceCreditAmount($creditAmountRemaining);
           $this->orderModel->setCompletedSamples(intval($order_reports['complete']));
           $this->orderModel->setRetestedSamples(intval($order_reports['retest']));
           $this->orderModel->setResampledSamples(intval($order_reports['resample']));
           $this->orderModel->save();
           $this->messageManager->addSuccessMessage(__("Order updated successfully with results submitted"));

          $url = $this->scopeConfig->getValue('hkgmain/hkg_general/order_reports', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
          if(!empty($url)) {
             $params = [
              'userid' => $this->orderModel->getCustomerId(),
              'storeid' => $this->orderModel->getStore()->getWebsiteId(),
              'orderid' => $this->orderModel->getIncrementId(),
              'credit_amount_remaining' => $creditAmountRemaining,
              'credit_amount_paid' => intval($order_reports['credit_amount_paid']),
              'order_report_platform' => 'magento',
              'result' => array('COMPLETE' => intval($order_reports['complete']) , 'RETEST' => intval($order_reports['retest']) , 'RESAMPLE' => intval($order_reports['resample']))
                ];
             $jsoneObject = $objectManager->create('Magento\Framework\Serialize\Serializer\Json');
             $jsonString = $jsoneObject->serialize($params);
             $objectManager->get(\Psr\Log\LoggerInterface::class)->info("json string".$jsonString, array());

             $this->_curl->setOption(CURLOPT_SSL_VERIFYHOST, false);
             $this->_curl->setOption(CURLOPT_SSL_VERIFYPEER, false);
             $this->_curl->setOption(CURLOPT_RETURNTRANSFER, true);
             $this->_curl->setOption(CURLOPT_TIMEOUT, 60);
             $this->_curl->addHeader("Content-Type", "application/json");
             $this->_curl->post($url, $jsonString);
             $response = $this->_curl->getBody(); 
             $objectManager->get(\Psr\Log\LoggerInterface::class)->info("response string ".$response, array()); 
          }
        }
        
      }
      
    } 
    catch(\Exception $e)
        {
          $objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), array()); 
        }

    return $resultRedirect->setPath('*/*/index');
 }

 public function getPrice() {

  foreach ($this->orderModel->getAllItems() as $item) {
          return  $item->getPrice();
         }
 }
}