<?php

namespace HKGDealers\AdvancePayment\Helper;

use HKGDealers\AdvancePayment\Model\HKGOrders;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{

    protected $quote;

    protected $quoteManagement;

    protected $customerFactory;

    protected $customerRepository;

    protected $orderService;

    protected $orderModel;

    protected $json;

    protected $productRepository;

    protected $cart;

    protected $checkoutSession;

    protected $customerSession;

	  /**
    * @param Magento\Framework\App\Helper\Context $context
    * @param Magento\Store\Model\StoreManagerInterface $storeManager
    * @param Magento\Catalog\Model\ProductRepository $product
    * @param Magento\Framework\Data\Form\FormKey $formKey $formkey,
    * @param Magento\Quote\Model\Quote $quote,
    * @param Magento\Customer\Model\CustomerFactory $customerFactory,
    * @param Magento\Sales\Model\Service\OrderService $orderService,
    */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Data\Form\FormKey $formkey,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Service\OrderService $orderService,
        HKGOrders $orderModel,
        \Magento\Framework\Serialize\Serializer\Json $json,
        CheckoutSession $checkoutSession,
        Cart $cart,
        Session $customerSession
    ) {
        $this->_storeManager = $storeManager;
        $this->productRepository = $productRepository;
        $this->_formkey = $formkey;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->orderService = $orderService;
        $this->orderModel = $orderModel;
        $this->json = $json;
        $this->checkoutSession = $checkoutSession;
        $this->cart = $cart;
        $this->customerSession = $customerSession;
        parent::__construct($context);
    }

     /**
     * Create Order On Your Store
     * 
     * @param array $orderData
     * @return array
     * 
    */
    public function createMageOrder($orderData) {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        try {
            $this->customerSession->setOrderSyncing('Initiated');    
            $context = array(); 
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info("Entered In second order", $context);
            $this->orderModel->load($orderData->getCustomerId(),'customer_id');
            $order = NULL;
            if($this->orderModel->getCustomerId() && !empty($this->orderModel->getCartProducts())) {
            $this->customerSession->setOrderSyncing('create');
            $store=$orderData->getStore();
            $websiteId = $orderData->getStore()->getWebsiteId();
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info("web id".$websiteId, $context);
            $customer = $this->customerFactory->create();
            $customer->setWebsiteId($websiteId);
            $customer->loadByEmail($orderData->getCustomerEmail());
            $quote=$this->quote->create(); //Create object of quote
            $quote->setStore($store); //set store for which you create quote
            // if you have allready buyer id then you can load customer directly 
            $customer= $this->customerRepository->getById($customer->getEntityId());
            $quote->setCurrency();
            $quote->assignCustomer($customer); //Assign quote to customer 
      
            //add items in quote
            $cartProductsJson = $this->orderModel->getCartProducts();
            $productData = $this->json->unserialize($cartProductsJson);
            foreach($productData['products'] as $key => $product )
                    {
                        $productObject = $this->productRepository->get($product['sku']);
                        $quote->addProduct($productObject,intval($product['qty']));
                    }
            $this->orderModel->setCartProducts("");
            $this->orderModel->save();
           
       /*   foreach($orderData['items'] as $item){
                $product=$this->_product->load($item['product_id']);
                $quote->addProduct(
                    $product,
                    intval($item['qty'])
                );
            } */
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info("orderaddress",$context);
            $objectManager->get(\Psr\Log\LoggerInterface::class)->info($this->json->serialize($this->getAddressInFull($orderData->getShippingAddress())), $context);
     
            //Set Address to quote
            $quote->getBillingAddress()->addData($this->getAddressInFull($orderData->getShippingAddress()));
            $quote->getShippingAddress()->addData($this->getAddressInFull($orderData->getShippingAddress()));
     
            // Collect Rates and Set Shipping & Payment Method
     
            $shippingAddress=$quote->getShippingAddress();
            $shippingAddress->setCollectShippingRates(true)
                            ->collectShippingRates()
                            ->setShippingMethod('freeshipping_freeshipping'); //shipping method
            $quote->setPaymentMethod('checkmo'); //payment method
            $quote->setInventoryProcessed(false); //not effetc inventory
            $quote->save(); //Now Save quote and your quote is ready
     
            // Set Sales Order Payment
            $quote->getPayment()->importData(['method' => 'checkmo']);
     
            // Collect Totals & Save Quote
            $quote->collectTotals()->save();
     
            // Create Order From Quote
            $order = $this->quoteManagement->submit($quote);
            
            $order->setEmailSent(0);
            $increment_id = $order->getRealOrderId();
            if($order->getEntityId()){
                $objectManager->get(\Psr\Log\LoggerInterface::class)->info("orderId".$order->getRealOrderId(),$context);
                //$result['order_id']= $order->getRealOrderId();
            }else{
                $result=['error'=>1,'msg'=>'Your custom message'];
            }
            
        }
         return $order;
       
    }
         catch(\Exception $e)
        {
             $context = array();
           $objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), $context); 
        }
    } 

 public function getAddressInFull($shippingAddress) {

    $telephone = $shippingAddress->getTelephone();
    if(empty($telephone)) {
        $telephone = 8856937845;
    }

  return array(
                'firstname'    => $shippingAddress->getFirstname(),
                'lastname'     => $shippingAddress->getLastname(),
                'prefix' => '',
                'suffix' => '',
                'street' => join(" ",$shippingAddress->getStreet()),
                'city' => $shippingAddress->getCity(),
                'country_id' => $shippingAddress->getCountryId(),
                'region' =>  $shippingAddress->getRegion(),
                'region_id' => $shippingAddress->getRegionId(), 
                'postcode' => $shippingAddress->getPostcode(),
                'telephone' => $telephone,
                'fax' => $shippingAddress->getFax(),
                'save_in_address_book' => 0
            );

 }


}