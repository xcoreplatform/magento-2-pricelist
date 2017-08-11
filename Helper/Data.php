<?php

namespace Dealer4dealer\Pricelist\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper
{
    protected $storeManager;
    protected $objectManager;

    const XML_PATH_GENERAL  = 'pricelist/general/';
    const XML_PATH_CUSTOMER = 'pricelist/customer/';
    const XML_PATH_CRON     = 'pricelist/cron/';


    public function __construct(Context $context,
                                ObjectManagerInterface $objectManager,
                                StoreManagerInterface $storeManager)
    {
        $this->objectManager = $objectManager;
        $this->storeManager  = $storeManager;
        parent::__construct($context);
    }

    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_GENERAL . $code, $storeId);
    }

    public function getCustomerConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CUSTOMER . $code, $storeId);
    }

    public function getCronConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_CRON . $code, $storeId);
    }
}

class GeneralConfig
{
    const ENABLED = 'enabled';
}

class CustomerConfig
{
    const ENABLED  = 'enabled';
    const DEFAULT  = 'default';
    const RUN_CRON = 'run_cron';
}

class CronConfig
{
    const EMPTY_GROUPS = 'empty_groups';
}