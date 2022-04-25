<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\AdvancePayment\Controller\Cart;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Checkout\Model\Cart as CustomerCart;
use Magento\Catalog\Api\ProductRepositoryInterface;
use HKGDealers\AdvancePayment\Model\HKGOrders;
/**
 * Controller for processing add to cart action.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart\Add 
{

     protected $orderModel;

     protected $customerSession;

     protected $json;

     /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param CustomerCart $cart
     * @param ProductRepositoryInterface $productRepository
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        CustomerCart $cart,
        ProductRepositoryInterface $productRepository,
        HKGOrders $orderModel,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart,
            $productRepository
        );
        $this->orderModel = $orderModel;
        $this->customerSession = $customerSession;
         $this->json = $json;
    }

    /**
     * Add product to shopping cart action
     *
     * @return ResponseInterface|ResultInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->messageManager->addErrorMessage(
                __('Your session has expired')
            );
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $params = $this->getRequest()->getParams();
        $secondOrderQuantity = 0;
        try {
            
            if (isset($params['qty'])) {
                $filter = new \Zend_Filter_LocalizedToNormalized(
                    ['locale' => $this->_objectManager->get(
                        \Magento\Framework\Locale\ResolverInterface::class
                    )->getLocale()]
                );
                $params['qty'] = $filter->filter($params['qty']);
            }

            $existingQty = $this->cart->getItemsQty();
            $splitQty = $this->getSplitQuantity();
            $currentQty = (int)$params['qty'];

            $totalQty = $existingQty + $splitQty + $currentQty;

            if($totalQty > 6) {
                $this->cart->truncate();
                $params['qty'] = strval($totalQty);
            }


            if ( $totalQty > 6) {
                $firstOrderQuantity = (int)$params['qty'];
                $firstOrderQuantity = ceil($firstOrderQuantity * 0.3);
                $secondOrderQuantity = (int)$params['qty'] - $firstOrderQuantity;
                $params['qty'] = strval($firstOrderQuantity);



            }

            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info("params ",$params);

            $product = $this->_initProduct();
            $related = $this->getRequest()->getParam('related_product');

            /** Check product availability */
            if (!$product) {
                return $this->goBack();
            }

            if( $secondOrderQuantity > 0)
                $this->saveSecondOrder($product, $secondOrderQuantity);

            $this->cart->addProduct($product, $params);
            if (!empty($related)) {
                $this->cart->addProductsByIds(explode(',', $related));
            }
            $this->cart->save();

            /**
             * @todo remove wishlist observer \Magento\Wishlist\Observer\AddToCart
             */
            $this->_eventManager->dispatch(
                'checkout_cart_add_product_complete',
                ['product' => $product, 'request' => $this->getRequest(), 'response' => $this->getResponse()]
            );

            if (!$this->_checkoutSession->getNoCartRedirect(true)) {
                if ($this->shouldRedirectToCart()) {
                    $message = __(
                        'You added %1 to your shopping cart.',
                        $product->getName()
                    );
                    $this->messageManager->addSuccessMessage($message);
                } else {
                    $this->messageManager->addComplexSuccessMessage(
                        'addCartSuccessMessage',
                        [
                            'product_name' => $product->getName(),
                            'cart_url' => $this->getCartUrl(),
                        ]
                    );
                }
                if ($this->cart->getQuote()->getHasError()) {
                    $errors = $this->cart->getQuote()->getErrors();
                    foreach ($errors as $error) {
                        $this->messageManager->addErrorMessage($error->getText());
                    }
                }
                return $this->goBack(null, $product);
            }
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            if ($this->_checkoutSession->getUseNotice(true)) {
                $this->messageManager->addNoticeMessage(
                    $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($e->getMessage())
                );
            } else {
                $messages = array_unique(explode("\n", $e->getMessage()));
                foreach ($messages as $message) {
                    $this->messageManager->addErrorMessage(
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($message)
                    );
                }
            }

            $url = $this->_checkoutSession->getRedirectUrl(true);
            if (!$url) {
                $url = $this->_redirect->getRedirectUrl($this->getCartUrl());
            }

            return $this->goBack($url);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage(
                $e,
                __('We can\'t add this item to your shopping cart right now.')
            );
            $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            return $this->goBack();
        }

        return $this->getResponse();
    }


    public function getSplitQuantity()
    {
       $qty = 0; 
       $customerId =  $this->customerSession->getCustomer()->getId();
       $this->orderModel->load($customerId,'customer_id');
       if($this->orderModel->getCustomerId() && !empty($this->orderModel->getCartProducts())) {
        $cartProductsJson = $this->orderModel->getCartProducts();
            $productData = $this->json->unserialize($cartProductsJson);
            foreach($productData['products'] as $key => $product )
                    {
                        $qty = $qty + intval($product['qty']);   
                    }


       }

       return $qty;
    }


    public function saveSecondOrder($product,$qty)
    {
        $context = array();
        try {
            $orderProduct = [];
            $cartProducts = "";
        $customerId = $this->customerSession->getCustomer()->getId();
        //$this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info("*****params*******".$customerId, $context);
        $this->orderModel->load($customerId,'customer_id');
        if(!$this->orderModel->getCustomerId()) {
            $this->orderModel->setCustomerId($customerId);

        }
       /* if(!empty($this->orderModel->getCartProducts())) {
           $products  = $this->updateQty($this->orderModel->getCartProducts(),$product->getSku(),$qty);
           $cartProducts = $this->json->serialize($products);
        } */
      //  else //if(empty($this->orderModel->getCartProducts()))
       // {
            $orderProduct[] = array('sku' => $product->getSku(),'qty' => $qty);
            $products  = [ 'products' => $orderProduct ];
            $cartProducts = $this->json->serialize($products);
      //  }
         
        $this->orderModel->setCartProducts($cartProducts);
        $this->orderModel->save();
        }
        catch(\Exception $e)
        {
             $context = array();
           $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), $context); 
        }
        
    }

    public function updateQty($cartProductsJson,$sku,$qty){
        try {
            $productData = $this->json->unserialize($cartProductsJson);
            $qtyUpdate = FALSE;
            foreach($productData['products'] as $key => $product )
                {
                   if($product['sku'] == $sku) {
                    $productData['products'][$key]['qty'] = (int)$product['qty'] + $qty ;
                    $qtyUpdate = TRUE;
                   }
                }
            if(!$qtyUpdate)
            {
               $productData['products'][] = array('sku' => $sku,'qty' => $qty);
            }
            return $productData;
            }
        catch(\Exception $e)
        {
             $context = array();
           $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), $context); 
        }
    } 


    public function log($params){

             $context = array();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $jsoneObject = $objectManager->create('Magento\Framework\Serialize\Serializer\Json');
            $jsonString = $jsoneObject->serialize($params);
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info("*****params*******", $context);
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($jsonString, $context); 
    }

    /**
     * Returns cart url
     *
     * @return string
     */
    private function getCartUrl()
    {
        return $this->_url->getUrl('checkout/cart', ['_secure' => true]);
    }

    /**
     * Is redirect should be performed after the product was added to cart.
     *
     * @return bool
     */
    private function shouldRedirectToCart()
    {
        return $this->_scopeConfig->isSetFlag(
            'checkout/cart/redirect_to_cart',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }
}
