<?php

namespace HKGDealers\AdvancePayment\Model;

use Magento\Framework\Model\AbstractModel;

class HKGOrders extends AbstractModel
{
    protected function _construct()
    {
        $this->_init(\HKGDealers\AdvancePayment\Model\ResourceModel\HKGOrders::class);
    }
    
}