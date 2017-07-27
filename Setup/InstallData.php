<?php

namespace Dealer4dealer\Pricelist\Setup;

use Magento\Customer\Setup\CustomerSetup;
use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Tax\Model\ClassModel;

class InstallData implements InstallDataInterface
{
    private $customerSetupFactory;

    /**
     * {@inheritdoc}
     */
    public function install(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        /** @var CustomerSetup $customerSetup */
        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerSetup->addAttribute('customer', 'price_list', [
            'type'     => 'int',
            'label'    => 'price_list',
            'input'    => 'select',
            'source'   => 'Dealer4dealer\Pricelist\Model\PriceList\Attribute\Source\PriceList',
            'required' => false,
            'visible'  => true,
            'position' => 500,
            'system'   => false,
            'backend'  => ''
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'price_list')
                                   ->addData(['used_in_forms' => [
                                       'adminhtml_customer'
                                   ]]);
        $attribute->save();

        $customerSetup->addAttribute('customer', 'vat_class', [
            'type'     => 'varchar',
            'label'    => 'vat_class',
            'input'    => 'select',
            'source'   => 'Dealer4dealer\Pricelist\Model\Customer\Attribute\Source\VatClass',
            'required' => false,
            'visible'  => true,
            'position' => 501,
            'system'   => false,
            'backend'  => ''
        ]);

        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'vat_class')
                                   ->addData(['used_in_forms' => [
                                       'adminhtml_customer'
                                   ]]);
        $attribute->save();


        /**
         * Install tax classes
         */
        $data = [
            [
                'class_name' => 'xCore No VAT',
                'class_type' => ClassModel::TAX_CLASS_TYPE_CUSTOMER,
            ],
            [
                'class_name' => 'xCore Excl VAT',
                'class_type' => ClassModel::TAX_CLASS_TYPE_CUSTOMER
            ],
            [
                'class_name' => 'xCore Incl VAT',
                'class_type' => ClassModel::TAX_CLASS_TYPE_CUSTOMER
            ],
        ];
        foreach ($data as $row) {
            $setup->getConnection()->insert($setup->getTable('tax_class'), $row);
        }

    }

    /**
     * Constructor
     *
     * @param \Magento\Customer\Setup\CustomerSetupFactory $customerSetupFactory
     */
    public function __construct(
        CustomerSetupFactory $customerSetupFactory
    )
    {
        $this->customerSetupFactory = $customerSetupFactory;
    }
}
