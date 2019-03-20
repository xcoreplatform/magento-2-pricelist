<?php

namespace Dealer4dealer\Pricelist\Helper;

use Dealer4dealer\Pricelist\Api\HelperDataInterface;
use Dealer4dealer\Pricelist\Helper\Codes\CronConfig;
use Dealer4dealer\Pricelist\Helper\Codes\CustomerConfig;
use Dealer4dealer\Pricelist\Helper\Codes\GeneralConfig;
use Dealer4dealer\Pricelist\Model\Setting;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper implements HelperDataInterface
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

    /**
     * @return \Dealer4dealer\Pricelist\Api\Data\SettingInterface[]
     */
    public function getAll()
    {
        $generalEnabled = new Setting;
        $generalEnabled->setField(self::XML_PATH_GENERAL . GeneralConfig::ENABLED);
        $generalEnabled->setValue($this->getGeneralConfig(GeneralConfig::ENABLED));

        $customerEnabled = new Setting;
        $customerEnabled->setField(self::XML_PATH_CUSTOMER . CustomerConfig::ENABLED);
        $customerEnabled->setValue($this->getCustomerConfig(CustomerConfig::ENABLED));

        $customerDefault = new Setting;
        $customerDefault->setField(self::XML_PATH_CUSTOMER . CustomerConfig::C_DEFAULT);
        $customerDefault->setValue($this->getCustomerConfig(CustomerConfig::C_DEFAULT));

        $customerRunCron = new Setting;
        $customerRunCron->setField(self::XML_PATH_CUSTOMER . CustomerConfig::RUN_CRON);
        $customerRunCron->setValue($this->getCustomerConfig(CustomerConfig::RUN_CRON));

        $cronEmptyGroups = new Setting;
        $cronEmptyGroups->setField(self::XML_PATH_CRON . CronConfig::EMPTY_GROUPS);
        $cronEmptyGroups->setValue($this->getCronConfig(CronConfig::EMPTY_GROUPS));

        return [
            $generalEnabled,
            $customerEnabled,
            $customerDefault,
            $customerRunCron,
            $cronEmptyGroups
        ];
    }

    /**
     * @param string $code
     * @return int
     */
    public function getGeneralConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_GENERAL . $code);
    }

    /**
     * @param string $code
     * @return int
     */
    public function getCustomerConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_CUSTOMER . $code);
    }

    /**
     * @param string $code
     * @return int
     */
    public function getCronConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_CRON . $code);
    }

    private function getConfigValue($field)
    {
        return $this->scopeConfig->getValue($field, ScopeInterface::SCOPE_STORE, null);
    }
}