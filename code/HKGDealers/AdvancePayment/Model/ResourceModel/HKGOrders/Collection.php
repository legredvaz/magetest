<?php
namespace HKGDealers\AdvancePayment\Model\ResourceModel\HKGOrders;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use HKGDealers\AdvancePayment\Model\HKGOrders as OrdersModel;
use HKGDealers\AdvancePayment\Model\ResourceModel\HKGOrders as OrdersResourceModel;

class Collection extends AbstractCollection
{

    protected $_idFieldName = 'entity_id';

    protected function _construct()
    {
        $this->_init(OrdersModel::class, OrdersResourceModel::class);
    }
}