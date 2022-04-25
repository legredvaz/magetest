<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\AdvancePayment\Controller\Cart;


use HKGDealers\AdvancePayment\Model\HKGOrders;

/**
 * Action Delete.
 *
 * Deletes item from cart.
 */
class Delete extends \Magento\Checkout\Controller\Cart\Delete 
{

   /* protected $customerSession;

    protected $orderModel;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
         HKGOrders $orderModel
    ) {
        $this->orderModel = $orderModel;
        $this->customerSession = $customerSession;
    } */

    /**
     * Delete shopping cart item action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            return $this->resultRedirectFactory->create()->setPath('*/*/');
        }

        $id = (int)$this->getRequest()->getParam('id');
        if ($id) {
            try {
               $this->removeHkgOrderRemainingQuantity($id);
                $this->cart->removeItem($id);
                // We should set Totals to be recollected once more because of Cart model as usually is loading
                // before action executing and in case when triggerRecollect setted as true recollecting will
                // executed and the flag will be true already.
                $this->cart->getQuote()->setTotalsCollectedFlag(false);
                $this->cart->save();              
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('We can\'t remove the item.'));
                $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->critical($e);
            }
        }
        $defaultUrl = $this->_objectManager->create(\Magento\Framework\UrlInterface::class)->getUrl('*/*');
        return $this->resultRedirectFactory->create()->setUrl($this->_redirect->getRedirectUrl($defaultUrl));
    }

    public function removeHkgOrderRemainingQuantity($itemId)
    {
        try {
            $customer = $this->_objectManager->get('\Magento\Customer\Model\Session');
            $orderModel  = $this->_objectManager->get('HKGDealers\AdvancePayment\Model\HKGOrders');
            $json = $this->_objectManager->get('\Magento\Framework\Serialize\Serializer\Json');
            $customerId = $customer->getCustomer()->getId();
            $orderModel->load($customerId,'customer_id');
            if(!empty($orderModel->getCustomerId())) {
               /* $item = $this->cart->getQuote()->getItemById($itemId);
                $productSku = $item->getSku();
                $productData = $json->unserialize($orderModel->getCartProducts());
            foreach($productData['products'] as $key => $product )
                {
                   if($product['sku'] == $productSku) {
                    unset($productData['products'][$key]);
                   }
                }
                $orderModel->setCartProducts($json->serialize($productData)); */
                $orderModel->setCartProducts("");
                } 
                $orderModel->save();
        }
        catch(\Exception $e)
        {
             $context = array();
           $this->_objectManager->get(\Psr\Log\LoggerInterface::class)->info($e->getMessage(), $context); 
        }


        
    } 

}
