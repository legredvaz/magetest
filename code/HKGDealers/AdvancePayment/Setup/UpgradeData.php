<?php
namespace HKGDealers\AdvancePayment\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

	private $eavSetupFactory;
 
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
 
	public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {
		

		if(version_compare($context->getVersion(), '1.2.0', '<')) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
             $objectManager->get(\Psr\Log\LoggerInterface::class)->info("upgrade started ", array());
			  $salesSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        	  $salesSetup->addAttribute(
              \Magento\Sales\Model\Order::ENTITY,
            'child_order',
            [
                'type'         => 'varchar',
                'label'        => 'Sub Order',
                'input'        => 'text',
                'sort_order'   => 110,
                'source'       => '',
                'global'       => 1,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => null,
                'visible_on_front' => false,
                'group'        => '',
                'backend'      => ''
            ]
        );

$objectManager->get(\Psr\Log\LoggerInterface::class)->info("upgrade done ", array());
		}

      /*  if(version_compare($context->getVersion(), '1.3.0', '<')) {
              $salesSetup = $this->eavSetupFactory->create(['setup' => $setup]);
              $salesSetup->addAttribute(
              \Magento\Sales\Model\Order::ENTITY,
            'use_credit_amount',
            [
                'type'         => 'varchar',
                'label'        => 'Used Credit Amount',
                'input'        => 'text',
                'sort_order'   => 120,
                'source'       => '',
                'global'       => 1,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => null,
                'visible_on_front' => false,
                'group'        => '',
                'backend'      => ''
            ]
        );
        } */



	}
}
