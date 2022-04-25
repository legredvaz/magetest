<?php
namespace HKGDealers\AdvancePayment\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\DB\Ddl\Table;

class UpgradeSchema implements UpgradeSchemaInterface
{
	public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context ) {
		$installer = $setup;

		$installer->startSetup();

		if(version_compare($context->getVersion(), '1.3.0', '<')) {
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'used_credit_amount',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'nullable' => true,
					'comment' => 'Credit Amount Used'
				]
			);


		}

		if(version_compare($context->getVersion(), '1.4.0', '<')) {
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'balance_credit_amount',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'nullable' => true,
					'comment' => 'Credit Amount Remaining'
				]
			);


		}

		if(version_compare($context->getVersion(), '1.5.0', '<')) {
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'child_order',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
					'nullable' => true,
					'comment' => 'Child Order'
				]
			);


		}

		if(version_compare($context->getVersion(), '1.6.0', '<')) {
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'completed_samples',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'nullable' => true,
					'comment' => 'Samples Completed'
				]
			);
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'retested_samples',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'nullable' => true,
					'comment' => 'Samples Retested'
				]
			);
			$installer->getConnection()->addColumn(
				$installer->getTable('sales_order'),
				'resampled_samples',
				[
					'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DECIMAL,
					'nullable' => true,
					'comment' => 'Samples Resampled'
				]
			);


		}


		$installer->endSetup();


	}
}
