<?php

namespace HKGDealers\AdvancePayment\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class HKGOrders extends AbstractDb
{
    protected function _construct()
    {
        $this->_init('hkg_orders', 'entity_id');
    }
}