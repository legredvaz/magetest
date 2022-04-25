<?php
namespace HKGDealers\AdvancePayment\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\InstallDataInterface;

class InstallData implements InstallDataInterface
{

	private $eavSetupFactory;
 
    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }
    
    /**
     * {@inheritdoc}
     */
	public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context ) {
		
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

	}
}
