<?php

namespace HKGDealers\AdvancePayment\Controller\Adminhtml\OldOrders;
use Magento\Backend\App\Action;

class Index extends \Magento\Backend\App\Action
{
/** @var \Magento\Framework\View\Result\PageFactory  */
protected $resultPageFactory;

public function __construct(
     Action\Context $context,
     \Magento\Framework\View\Result\PageFactory $resultPageFactory
) {
     $this->resultPageFactory = $resultPageFactory;
     parent::__construct($context);
}
/**
* Load the page defined in view/adminhtml/layout/samplenewpage_sampleform_index.xml
*
* @return \Magento\Framework\View\Result\Page
*/
public function execute()
{
     return $this->resultPageFactory->create();
}
}