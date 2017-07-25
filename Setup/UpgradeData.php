<?php

namespace Dealer4dealer\Pricelist\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;

class UpgradeData implements UpgradeDataInterface
{

    /**
     * {@inheritdoc}
     */
    public function upgrade(
        ModuleDataSetupInterface $setup,
        ModuleContextInterface $context
    )
    {
        $setup->startSetup();
        if (version_compare($context->getVersion(), "0.0.1", "<")) {
            //Your upgrade script
        }
        $setup->endSetup();
    }
}
