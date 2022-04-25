<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace HKGDealers\AdvancePayment\Model;

use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
/**
 * Class DataProvider
 */
class DataProvider extends \Magento\Ui\DataProvider\AbstractDataProvider
{
/**
* Get data
*
* @return array
*/
public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $orderCollectionFactory,
        array $meta = [],
        array $data = []
    ) {
        $this->collection = $orderCollectionFactory->create();
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
    }

public function getData()
{
     return [];
}
}