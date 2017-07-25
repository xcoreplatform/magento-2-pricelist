<?php

namespace Dealer4dealer\Pricelist\Setup;

use Magento\Customer\Setup\CustomerSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

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
        //Your install script

        $customerSetup = $this->customerSetupFactory->create(['setup' => $setup]);

        $customerSetup->addAttribute('customer', 'price_list', [
            'type'     => 'int',
            'label'    => 'price_list',
            'input'    => 'select',
            'source'   => 'Magento\Customer\Model\Customer\Attribute\Source\Group',
            'required' => false,
            'visible'  => true,
            'position' => 333,
            'system'   => false,
            'backend'  => ''
        ]);


        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'price_list')
                                   ->addData(['used_in_forms' => [
                                       'adminhtml_customer'
                                   ]]);
        $attribute->save();


        $customerSetup->addAttribute('customer', 'vat_liable', [
            'type'     => 'int',
            'label'    => 'vat_liable',
            'input'    => 'select',
            'source'   => 'Magento\Customer\Model\Customer\Attribute\Source\Group',
            'required' => true,
            'visible'  => true,
            'position' => 333,
            'system'   => false,
            'backend'  => ''
        ]);


        $attribute = $customerSetup->getEavConfig()->getAttribute('customer', 'vat_liable')
                                   ->addData(['used_in_forms' => [
                                       'adminhtml_customer'
                                   ]]);
        $attribute->save();
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
