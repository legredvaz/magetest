<?php
namespace HKGDealers\AdvancePayment\Block\Adminhtml\Order;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Class SaveButton
 */
class SaveButton implements ButtonProviderInterface
{
    /**
     * @return array
     */
    public function getButtonData()
    {
        /* 'data_attribute' => [
                'mage-init' => ['button' => ['event' => 'save']],
                'form-role' => 'save',
            ], */
        return [
            'label' => __('Save Report'),
            'class' => 'save primary',
            'sort_order' => 90
        ];
    }
}