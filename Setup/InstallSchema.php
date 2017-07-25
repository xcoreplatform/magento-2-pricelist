<?php

namespace Dealer4dealer\Pricelist\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    /**
     * {@inheritdoc}
     */
    public function install(
        SchemaSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $installer = $setup;
        $installer->startSetup();

        $table_dealer4dealer_price_list = $setup->getConnection()->newTable($setup->getTable('dealer4dealer_price_list'));

        $table_dealer4dealer_price_list
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'identity' => true, 'auto_increment' => true, 'unsigned' => true, 'primary' => true],
                'Price List ID'
            )
            ->addColumn(
                'guid',
                Table::TYPE_TEXT,
                36,
                ['nullable' => false],
                'Price List GUID'
            )
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                null,
                ['nullable' => false],
                'Price List Code'
            )
            ->addIndex(
                'IDX_D4D_PRICE_LIST_GUID',
                ['guid'],
                ['type' => 'UNIQUE']
            )
            ->setComment('xCore Price List Table');

        $setup->getConnection()->createTable($table_dealer4dealer_price_list);

        $table_dealer4dealer_price_list_item = $setup->getConnection()->newTable($setup->getTable('dealer4dealer_price_list_item'));

        $table_dealer4dealer_price_list_item
            ->addColumn(
                'id',
                Table::TYPE_INTEGER,
                null,
                ['nullable' => false, 'identity' => true, 'auto_increment' => true, 'primary' => true, 'unsigned' => true],
                'Price List Item ID'
            )
            ->addColumn(
                'price_list_id',
                Table::TYPE_INTEGER,
                10,
                ['nullable' => false, 'unsigned' => true],
                'Price List ID'
            )
            ->addColumn(
                'product_id',
                Table::TYPE_INTEGER,
                10,
                ['nullable' => false, 'unsigned' => true],
                'Price List Item Product ID'
            )
            ->addColumn(
                'qty',
                Table::TYPE_DECIMAL,
                null,
                [
                    'nullable'  => false,
                    'scale'     => 4,
                    'precision' => 12,
                    'default'   => 1.0000
                ],
                'Price List Item Quantity'
            )
            ->addColumn(
                'price',
                Table::TYPE_DECIMAL,
                null,
                [
                    'nullable'  => false,
                    'scale'     => 4,
                    'precision' => 12,
                    'default'   => 0.0000
                ],
                'Price List Item Price'
            )
            ->addColumn(
                'start_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Price List Item Start Date'
            )
            ->addColumn(
                'end_date',
                Table::TYPE_DATETIME,
                null,
                [],
                'Price List Item End Date'
            )
            ->addIndex(
                'IDX_PRICE_LIST_ID_PRODUCT_ID_QTY',
                ['price_list_id', 'product_id', 'qty'],
                ['type' => 'UNIQUE']
            )
            ->addForeignKey(
                'FK_PRICE_LIST_ID',
                'price_list_id',
                'dealer4dealer_price_list',
                'id',
                Table::ACTION_CASCADE
            )
            ->addForeignKey(
                'FK_PRODUCT_ID',
                'product_id',
                'catalog_product_entity',
                'entity_id',
                Table::ACTION_CASCADE
            )
            ->setComment('xCore Price List Item Table');

        $setup->getConnection()->createTable($table_dealer4dealer_price_list_item);

        $setup->endSetup();
    }
}
