<?php

namespace Dealer4dealer\Pricelist\Helper;

use Dealer4dealer\Pricelist\Api\HelperDataInterface;
use Dealer4dealer\Pricelist\Helper\Codes\CronConfig;
use Dealer4dealer\Pricelist\Helper\Codes\CustomerConfig;
use Dealer4dealer\Pricelist\Helper\Codes\GeneralConfig;
use Dealer4dealer\Pricelist\Model\Setting;
use Magento\Cron\Model\ResourceModel\Schedule\Collection;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

class Data extends AbstractHelper implements HelperDataInterface
{
    protected $storeManager;
    protected $objectManager;
    protected $cronCollection;
    const XML_PATH_GENERAL  = 'pricelist/general/';
    const XML_PATH_CUSTOMER = 'pricelist/customer/';
    const XML_PATH_CRON     = 'pricelist/cron/';

    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        StoreManagerInterface $storeManager,
        Collection $cronCollection
    ) {
        $this->objectManager  = $objectManager;
        $this->storeManager   = $storeManager;
        $this->cronCollection = $cronCollection;
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

        $cronItemsPerRun = new Setting;
        $cronItemsPerRun->setField(self::XML_PATH_CRON . CronConfig::ITEMS_PER_RUN);
        $cronItemsPerRun->setValue($this->getCronConfig(CronConfig::ITEMS_PER_RUN));

        $cronLastRun = new Setting;
        $cronLastRun->setField(self::XML_PATH_CRON . CronConfig::LAST_RUN);
        $cronLastRun->setValue($this->getLastRunOfCronJob('xcore_generate_tier_prices'));

        $cronNextRun = new Setting;
        $cronNextRun->setField(self::XML_PATH_CRON . CronConfig::NEXT_RUN);
        $cronNextRun->setValue($this->getNextRunOfCronJob('xcore_generate_tier_prices'));

        return [
            $generalEnabled,
            $customerEnabled,
            $customerDefault,
            $customerRunCron,
            $cronEmptyGroups,
            $cronItemsPerRun,
            $cronLastRun,
            $cronNextRun,
        ];
    }

    private function getLastRunOfCronJob($cronCode)
    {
        $lastSuccessJobs = $this->getCronJobsByCode($cronCode);
        $lastSuccessJobs = array_reverse($lastSuccessJobs);
        foreach($lastSuccessJobs as $lastSuccessJob) {
            if($lastSuccessJob->getStatus() == 'success') {
                return $lastSuccessJob->getFinishedAt();
            }
        }
        return false;
    }

    private function getNextRunOfCronJob($cronCode)
    {
        $nextJobs = $this->getCronJobsByCode($cronCode);
        foreach($nextJobs as $nextJob) {
            if($nextJob->getStatus() == 'pending') {
                return $nextJob->getScheduledAt();
            }
        }
        return false;
    }

    private function getCronJobsByCode($cronCode)
    {
        return $this->cronCollection
            ->clear()
            ->addFieldToFilter('job_code', ['eq' => $cronCode])
            ->setOrder('schedule_id', Collection::SORT_ORDER_ASC)
            ->getItems();
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public function getGeneralConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_GENERAL . $code);
    }

    /**
     * @param string $code
     *
     * @return int
     */
    public function getCustomerConfig($code)
    {
        return $this->getConfigValue(self::XML_PATH_CUSTOMER . $code);
    }

    /**
     * @param string $code
     *
     * @return mixed
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