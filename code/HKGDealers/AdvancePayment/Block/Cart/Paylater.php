<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\AdvancePayment\Block\Cart;

use HKGDealers\AdvancePayment\Model\HKGOrders;

class Paylater extends \Magento\Framework\View\Element\Template
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    protected $orderModel;

    protected $productRepository;

    protected $json;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        array $data = [],
        HKGOrders $orderModel,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Serialize\Serializer\Json $json
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->orderModel = $orderModel;
        $this->productRepository = $productRepository;
        $this->json = $json;
    }


    public function getPayLaterAmount()
    {
       $paylaterAmount = 0;
       $customerId =  $this->customerSession->getCustomer()->getId();
       $this->orderModel->load($customerId,'customer_id');
       if($this->orderModel->getCustomerId() && !empty($this->orderModel->getCartProducts())) {
        $cartProductsJson = $this->orderModel->getCartProducts();
            $productData = $this->json->unserialize($cartProductsJson);
            foreach($productData['products'] as $key => $product )
                    {
                        $productObject = $this->productRepository->get($product['sku']);
                        $paylaterAmount = $paylaterAmount + ($productObject->getPrice() * intval($product['qty']));   
                    }


       }

       return $paylaterAmount;
    }


}