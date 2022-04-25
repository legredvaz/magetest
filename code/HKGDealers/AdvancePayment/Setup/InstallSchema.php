<?php

namespace HKGDealers\AdvancePayment\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;

class InstallSchema implements InstallSchemaInterface
{
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        // Get tutorial_simplenews table
        $tableName = $installer->getTable('hkg_orders');
        // Check if the table already exists
        if ($installer->getConnection()->isTableExists($tableName) != true) {
            // Create tutorial_simplenews table
            $table = $installer->getConnection()
                ->newTable($tableName)
                ->addColumn(
                    'entity_id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Entity ID'
                )
                ->addColumn(
                    'customer_id',
                    Table::TYPE_INTEGER,
                    255,
                    ['nullable' => false],
                    'Customer Id'
                )
                ->addColumn(
                    'cart_products',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => true],
                    'Cart Products'
                )
                ->setComment('Cart Products');
            $installer->getConnection()->createTable($table);
        }

        $installer->endSetup();
    }
}