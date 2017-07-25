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

        $table_dealer4dealer_price_list->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'identity' => true, 'auto_increment' => true, 'unsigned' => true, 'primary' => true],
            'Price List ID'
        );

        $table_dealer4dealer_price_list->addColumn(
            'guid',
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Price List GUID'
        );

        $table_dealer4dealer_price_list->addColumn(
            'code',
            Table::TYPE_TEXT,
            null,
            ['nullable' => false],
            'Price List Code'
        );

        $table_dealer4dealer_price_list->addIndex(
            'IDX_D4D_PRICE_LIST_GUID',
            ['guid'],
            ['type' => 'UNIQUE']
        );


        $table_dealer4dealer_price_list_item = $setup->getConnection()->newTable($setup->getTable('dealer4dealer_price_list_item'));

        $table_dealer4dealer_price_list_item->addColumn(
            'id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false, 'identity' => true, 'auto_increment' => true, 'primary' => true, 'unsigned' => true],
            'Price List Item ID'
        );

        $table_dealer4dealer_price_list_item->addColumn(
            'price_list_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Price List ID'
        );

        $table_dealer4dealer_price_list_item->addColumn(
            'product_id',
            Table::TYPE_INTEGER,
            null,
            ['nullable' => false],
            'Price List Item Product ID'
        );

        $table_dealer4dealer_price_list_item->addColumn(
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
        );

        $table_dealer4dealer_price_list_item->addColumn(
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
        );

        $table_dealer4dealer_price_list_item->addColumn(
            'start_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Price List Item Start Date'
        );

        $table_dealer4dealer_price_list_item->addColumn(
            'end_date',
            Table::TYPE_DATETIME,
            null,
            [],
            'Price List Item End Date'
        );

        $table_dealer4dealer_price_list_item->addIndex(
            'IDX_D4D_PRICE_LIST_ID_PRODUCT_ID_QTY',
            ['price_list_id', 'product_id', 'qty'],
            ['type' => 'UNIQUE']
        );

        $table_dealer4dealer_price_list_item->addForeignKey(
            'FK_D4D_PRICE_LIST_ID',
            'price_list_id',
            $installer->getTable('dealer4dealer_price_list'),
            'id',
            Table::ACTION_CASCADE
        );


        $table_dealer4dealer_price_list_item->addForeignKey(
            'FK_PRODUCT_ID',
            'product_id',
            $installer->getTable('catalog_product_entity'),
            'entity_id',
            Table::ACTION_CASCADE
        );

        $setup->getConnection()->createTable($table_dealer4dealer_price_list_item);

        $setup->getConnection()->createTable($table_dealer4dealer_price_list);

        $setup->endSetup();
    }
}
