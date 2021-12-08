<?php

namespace Dealer4dealer\Pricelist\Observer\Customer;

use Dealer4dealer\Pricelist\Cron\PriceListCron;
use Dealer4dealer\Pricelist\Helper\Codes\CustomerConfig;
use Dealer4dealer\Pricelist\Helper\Codes\GeneralConfig;
use Dealer4dealer\Pricelist\Helper\Data;
use Dealer4dealer\Pricelist\Model\XcoreTaxClass;
use Dealer4dealer\Xcore\Api\Data\PriceListItemInterface;
use Dealer4dealer\Xcore\Api\PriceListItemRepositoryInterface;
use Dealer4dealer\Xcore\Api\PriceListRepositoryInterface;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\TaxClass\Repository as TaxClassRepository;
use Psr\Log\LoggerInterface;

class SaveAfter implements ObserverInterface
{
    private $logger;
    private $helper;
    private $searchCriteriaBuilder;
    private $customerRepository;
    private $customerGroupRepository;
    private $customerGroupFactory;
    private $taxClassRepository;
    private $priceListRepository;
    private $priceListItemRepository;
    private $cronJob;
    private $customerPriceList;
    private $customerVatClass;
    private $customerGroupId;
    private $groupCode;
    private $groupId;
    private $taxClassId;

    /**
     * Constructor.
     *
     * @param LoggerInterface                  $logger
     * @param Data                             $helper
     * @param SearchCriteriaBuilder            $searchCriteriaBuilder
     * @param CustomerRepository               $customerRepository
     * @param GroupRepository                  $groupRepository
     * @param GroupInterfaceFactory            $groupFactory
     * @param TaxClassRepository               $taxRepository
     * @param PriceListRepositoryInterface     $priceListRepository
     * @param PriceListItemRepositoryInterface $priceListItemRepository
     * @param PriceListCron                    $cronJob
     */
    public function __construct(
        LoggerInterface $logger,
        Data $helper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        CustomerRepository $customerRepository,
        GroupRepository $groupRepository,
        GroupInterfaceFactory $groupFactory,
        TaxClassRepository $taxRepository,
        PriceListRepositoryInterface $priceListRepository,
        PriceListItemRepositoryInterface $priceListItemRepository,
        PriceListCron $cronJob
    ) {
        $this->logger                  = $logger;
        $this->helper                  = $helper;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->customerRepository      = $customerRepository;
        $this->customerGroupRepository = $groupRepository;
        $this->customerGroupFactory    = $groupFactory;
        $this->taxClassRepository      = $taxRepository;
        $this->cronJob                 = $cronJob;
        $this->priceListRepository     = $priceListRepository;
        $this->priceListItemRepository = $priceListItemRepository;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleEnabled() == false) {
            return;
        }

        if ($this->updateGroupEnabled() == false) {
            return;
        }

        /** @var Customer $customer */
        $customer = $observer->getData('customer_data_object');

        $this->customerPriceList = $customer->getCustomAttribute('price_list');
        if ($this->customerPriceList) {
            $this->customerPriceList = $this->customerPriceList->getValue();
        }

        $this->customerVatClass = $customer->getCustomAttribute('vat_class');
        if ($this->customerVatClass) {
            $this->customerVatClass = $this->customerVatClass->getValue();
        }

        $this->customerGroupId = $customer->getGroupId();

        if ($this->customerPriceList) {
            $this->createGroupCode();

            $this->createGroupIfNeeded();

            if ($this->customerGroupId != $this->groupId) {
                $customer->setGroupId($this->groupId);

                $this->customerRepository->save($customer);
            }
        }
    }

    /**
     * Generates a group code based on price list id and vat class.
     */
    private function createGroupCode()
    {
        $this->setTaxClassId();

        $priceList = $this->priceListRepository->getById($this->customerPriceList);

        $taxClass = $this->taxClassRepository->get($this->taxClassId);

        $taxAddition = '';
        switch ($taxClass->getClassName()) {
            case XcoreTaxClass::WITH_VAT:
                $taxAddition = ' with vat';
                break;
            case XcoreTaxClass::WITHOUT_VAT:
                $taxAddition = ' without vat';
                break;
        }

        if ($priceList) {
            $this->groupCode = sprintf('PL %s%s #%s', $priceList->getCode(), $taxAddition, $priceList->getId());
        }
    }

    private function createGroupIfNeeded()
    {
        $searchForGroup = $this->searchForGroup();

        if ($searchForGroup) {
            return;
        }

        $this->setTaxClassId();

        $this->createGroup();
    }

    /**
     * Finds the group with the global var $_groupCode
     * and set the $_groupId if a group has been found.
     *
     * @return bool
     */
    private function searchForGroup()
    {
        $searchCriteria  = $this->searchCriteriaBuilder->setFilterGroups([])
                                                       ->addFilter('customer_group_code', $this->groupCode)
                                                       ->create();
        $groupCollection = $this->customerGroupRepository->getList($searchCriteria);

        if ($groupCollection->getItems()) {
            $this->groupId = $groupCollection->getItems()[0]->getId();
            return true;
        }

        return false;
    }

    /**
     * Sets the global var $_taxClassId. The Tax Classes have been
     * added to the tax_class table on installation of this module.
     */
    private function setTaxClassId()
    {
        switch ($this->customerVatClass) {
            case null:
                $className = 'xCore No VAT'; // deprecated
                break;
            case 'including':
                $className = XcoreTaxClass::WITH_VAT;
                break;
            case 'excluding':
                $className = XcoreTaxClass::WITHOUT_VAT;
                break;
            default:
                throw new \Exception('No match on customer VAT class.');
        }

        $searchCriteria     = $this->searchCriteriaBuilder->setFilterGroups([])
                                                          ->addFilter('class_name', $className)
                                                          ->create();
        $taxClassCollection = $this->taxClassRepository->getList($searchCriteria);

        if ($taxClassCollection->getItems()) {
            /** @var ClassModel $model */
            $model            = array_values($taxClassCollection->getItems())[0];
            $this->taxClassId = $model->getClassId();
            return;
        }

        throw new \Exception(sprintf('Tax Class with name %s not found', $className));
    }

    /**
     * Create the customer_group and set $_groupId
     * to the id of the group created in this method.
     */
    private function createGroup()
    {
        /** @var Group $group */
        $group = $this->customerGroupFactory->create();
        $group->setCode($this->groupCode);
        $group->setTaxClassId($this->taxClassId);

        $newGroup = $this->customerGroupRepository->save($group);

        $this->setProcessedToFalseForPriceList();

        if ($this->runCronOnNewGroup()) {
            $this->cronJob->execute();
        }

        $this->groupId = $newGroup->getId();
    }

    private function moduleEnabled()
    {
        $status = $this->helper->getGeneralConfig(GeneralConfig::ENABLED);
        if (!$status) {
            $this->logger->info('Dealer4Dealer Price List setting -- Module enabled = false: skipping Observer/Customer/SaveAfter::execute()');
        }

        return $status;
    }

    private function updateGroupEnabled()
    {
        $status = $this->helper->getCustomerConfig(CustomerConfig::ENABLED);
        if (!$status) {
            $this->logger->info(
                'Dealer4Dealer Price List setting -- Update group on customer save = false: skipping Observer/Customer/SaveAfter::execute()'
            );
        }

        return $status;
    }

    private function getDefaultCustomerGroup()
    {
        return $this->helper->getCustomerConfig(CustomerConfig::C_DEFAULT);
    }

    private function runCronOnNewGroup()
    {
        return $this->helper->getCustomerConfig(CustomerConfig::RUN_CRON);
    }

    private function setProcessedToFalseForPriceList()
    {
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                      ->addFilter(PriceListItemInterface::PRICE_LIST_ID, $this->customerPriceList)
                                                      ->create();
        $itemCollection = $this->priceListItemRepository->getList($searchCriteria);

        foreach ($itemCollection->getItems() as $item) {
            $item->setProcessed(0);
            $this->priceListItemRepository->save($item);
        }
    }
}
