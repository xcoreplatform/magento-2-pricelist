<?php

namespace Dealer4dealer\Pricelist\Cron;

use Dealer4dealer\Pricelist\Api\PriceListCronInterface;
use Dealer4dealer\Pricelist\Helper\Codes\CronConfig;
use Dealer4dealer\Pricelist\Helper\Codes\GeneralConfig;
use Dealer4dealer\Pricelist\Helper\Data;
use Dealer4dealer\Pricelist\Model\CronResult;
use Dealer4dealer\Pricelist\Model\CustomerGroup;
use Dealer4dealer\Pricelist\Model\XcoreTaxClass;
use Dealer4dealer\Xcore\Api\Data\PriceListInterface;
use Dealer4dealer\Xcore\Api\Data\PriceListItemInterface;
use Dealer4dealer\Xcore\Api\PriceListItemRepositoryInterface;
use Dealer4dealer\Xcore\Api\PriceListRepositoryInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Model\Product\TierPriceManagement;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\BaseFactory;
use Magento\Tax\Api\Data\TaxClassInterface;
use Magento\Tax\Model\TaxClass\Repository as TaxClassRepository;
use Psr\Log\LoggerInterface;

class PriceListCron implements PriceListCronInterface
{
    const DISABLED_MSG      = 'Dealer4Dealer Price List setting -- Module enabled = false: skipping Cron/PriceListCron::execute()';
    const EXECUTE_MSG       = 'Executing Cron : Creating Tier Prices based on xCore Price Lists';
    const COMPLETED_MSG     = 'Completed Cron : Removed %s and added/updated %s Tier Price(s)';
    const FAILED_REMOVE_MSG = 'Failed to remove tier price with SKU %s for customer group %s';
    const FAILED_ADD_MSG    = 'Failed to add tier price with SKU %s for customer group %s';

    private $logger;
    private $helper;
    private $searchCriteriaBuilder;
    private $customerGroupRepository;
    private $customerGroupFactory;
    private $taxClassRepository;
    private $priceListRepository;
    private $priceListItemRepository;
    private $tierPriceManagement;

    private $allPriceLists;
    private $activePriceListIds;
    /** @var CustomerGroup[] $allCustomerGroups */
    private $allCustomerGroups;
    private $allTaxClasses;

    private $itemsToProcess    = [];
    private $removedTierPrices = 0;
    private $addedTierPrices   = 0;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger
     * @param Data $helper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param GroupRepository $groupRepository
     * @param GroupInterfaceFactory|BaseFactory $groupFactory
     * @param TaxClassRepository $taxRepository
     * @param PriceListRepositoryInterface $priceListRepository
     * @param PriceListItemRepositoryInterface $priceListItemRepository
     * @param TierPriceManagement $tierPriceManagement
     */
    public function __construct(LoggerInterface $logger,
                                Data $helper,
                                SearchCriteriaBuilder $searchCriteriaBuilder,
                                GroupRepository $groupRepository,
                                GroupInterfaceFactory $groupFactory,
                                TaxClassRepository $taxRepository,
                                PriceListRepositoryInterface $priceListRepository,
                                PriceListItemRepositoryInterface $priceListItemRepository,
                                TierPriceManagement $tierPriceManagement)
    {
        $this->logger                  = $logger;
        $this->helper                  = $helper;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->customerGroupRepository = $groupRepository;
        $this->customerGroupFactory    = $groupFactory;
        $this->taxClassRepository      = $taxRepository;
        $this->priceListRepository     = $priceListRepository;
        $this->priceListItemRepository = $priceListItemRepository;
        $this->tierPriceManagement     = $tierPriceManagement;
    }

    /**
     * Execute the cron
     *
     * @return \Dealer4dealer\Pricelist\Api\Data\CronResultInterface|string
     */
    public function execute()
    {
        if ($this->moduleEnabled() == false) return self::DISABLED_MSG;

        $this->logger->info(self::EXECUTE_MSG);

        $this->setAllLists();

        if ($this->createEmptyGroups()) $this->createGroups();

        $this->setAllGroups();

        $this->setItemsToRemove();

        $this->setItemsToAdd();

        foreach ($this->itemsToProcess as $sku => $process) {

            if (isset($process['remove']))
                $this->removeTierPrices($sku, $process['remove']);

            if (isset($process['add']))
                $this->createTierPrices($sku, $process['add']);
        }

        $result = new CronResult;
        $result->setRemoved($this->removedTierPrices);
        $result->setAddedOrUpdated($this->addedTierPrices);

        $this->logger->info(sprintf(self::COMPLETED_MSG, $result->getRemoved(), $result->getAddedOrUpdated()));

        return $result;
    }

    /**
     * Removes tier prices.
     *
     * @param string $sku
     * @param PriceListItemInterface[][] $process
     */
    private function removeTierPrices($sku, $process)
    {
        foreach ($process as $priceList => $priceListItems) {

            $groups = $this->getGroups($priceList);

            foreach ($groups as $group) {

                $this->removeTierPricesForGroup($sku, $group, $priceListItems);
            }
        }
    }

    /**
     * Removes tier prices for a specific customer group.
     *
     * @param string $sku
     * @param CustomerGroup $group
     * @param PriceListItemInterface[] $priceListItems
     */
    private function removeTierPricesForGroup($sku, $group, $priceListItems)
    {
        $tierPrices = $this->tierPriceManagement->getList($sku, $group->id);

        /** @var ProductTierPriceInterface $tierPrice */
        foreach ($tierPrices as $tierPrice) {

            foreach ($priceListItems as $priceListItem) {

                if ($tierPrice->getQty() == $priceListItem->getQty()) {

                    try {

                        $this->tierPriceManagement->remove($sku, $group->id, floatval($priceListItem->getQty()));

                        $this->priceListItemRepository->delete($priceListItem);

                        $this->removedTierPrices++;

                    } catch (\Exception $exception) {

                        $this->logger->error(sprintf(self::FAILED_REMOVE_MSG, $sku, $group->id));
                    }
                }
            }
        }
    }

    /**
     * Creates tier prices.
     *
     * @param string $sku
     * @param PriceListItemInterface[][] $process
     */
    private function createTierPrices($sku, $process)
    {
        foreach ($process as $priceList => $priceListItems) {

            $groups = $this->getGroups($priceList);

            foreach ($groups as $group) {

                $this->createTierPricesForGroup($sku, $group, $priceListItems);
            }
        }
    }

    /**
     * Creates tier prices for a specific customer group.
     *
     * @param string $sku
     * @param CustomerGroup $group
     * @param PriceListItemInterface[] $priceListItems
     */
    private function createTierPricesForGroup($sku, $group, $priceListItems)
    {
        foreach ($priceListItems as $priceListItem) {

            try {

                $this->tierPriceManagement->add($sku, $group->id, floatval($priceListItem->getPrice()), floatval($priceListItem->getQty()));

                $this->addedTierPrices++;

            } catch (\Exception $exception) {

                $this->logger->error(sprintf(self::FAILED_ADD_MSG, $sku, $group->id));
            }

            $priceListItem->setProcessed(1);
            $this->priceListItemRepository->save($priceListItem);
        }
    }

    /**
     * Set items that should be removed as tier prices. Rows comply to:
     * - having an end date that's passed
     * - being processed before
     */
    private function setItemsToRemove()
    {
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                      ->addFilter('end_date', date('Y-m-d'), 'lt')
                                                      ->addFilter(PriceListItemInterface::PROCESSED, '1')
                                                      ->create();
        $itemCollection = $this->priceListItemRepository->getList($searchCriteria);

        foreach ($itemCollection->getItems() as $item) {

            $this->itemsToProcess[$item->getProductSku()]['remove'][$item->getPriceListId()][] = $item;
        }
    }

    /**
     * Set items of which tier prices should be created. Rows comply only to:
     * - not being processed before
     */
    private function setItemsToAdd()
    {
        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                      ->addFilter(PriceListItemInterface::PROCESSED, '0')
                                                      ->addFilter(PriceListItemInterface::PRICE_LIST_ID, $this->activePriceListIds, 'in')
                                                      ->create();
        $itemCollection = $this->priceListItemRepository->getList($searchCriteria);

        foreach ($itemCollection->getItems() as $item) {

            if ($item->getEndDate() != null && $item->getEndDate() < date('Y-m-d')) {

                $this->priceListItemRepository->delete($item);

                $this->removedTierPrices++;

                continue;
            }

            if ($item->getStartDate() != null && $item->getStartDate() > date('Y-m-d'))
                continue;

            $this->itemsToProcess[$item->getProductSku()]['add'][$item->getPriceListId()][] = $item;
        }
    }

    /**
     * Will set $this->allPriceLists with all price lists synchronised from the xCore.
     */
    private function setAllLists()
    {
        $this->allPriceLists = [];

        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                      ->create();
        $listCollection = $this->priceListRepository->getList($searchCriteria);

        foreach ($listCollection->getItems() as $item) {

            $this->allPriceLists[] = $item;
        }
    }

    private function setAllGroups()
    {
        $this->allCustomerGroups = [];

        $searchCriteria  = $this->searchCriteriaBuilder->setFilterGroups([])
                                                       ->addFilter('customer_group_code', 'xCore Price List %', 'like')
                                                       ->create();
        $groupCollection = $this->customerGroupRepository->getList($searchCriteria);

        foreach ($groupCollection->getItems() as $item) {

            $priceListId = filter_var($item->getCode(), FILTER_SANITIZE_NUMBER_INT);

            $group               = new CustomerGroup();
            $group->id           = $item->getId();
            $group->priceListId  = $priceListId;
            $group->taxClassId   = $item->getTaxClassId();
            $group->taxClassName = $item->getTaxClassName();

            $this->allCustomerGroups[] = $group;
        }

        $this->setAllListIdsToCreateTierPricesFor();
    }

    private function setAllListIdsToCreateTierPricesFor()
    {
        $this->activePriceListIds = '';

        $list = [];

        foreach ($this->allCustomerGroups as $group) {

            if (!isset($list[$group->priceListId])) {

                $list[$group->priceListId] = 'added';

                $this->activePriceListIds .= $group->priceListId . ',';
            }
        }

        rtrim($this->activePriceListIds, ',');
    }

    private function setAllTaxClasses()
    {
        $this->allTaxClasses = [];

        $searchCriteria     = $this->searchCriteriaBuilder->setFilterGroups([])
                                                          ->addFilter('class_name',
                                                                      XcoreTaxClass::NO_VAT . ',' .
                                                                      XcoreTaxClass::EXCL_VAT . ',' .
                                                                      XcoreTaxClass::INCL_VAT,
                                                                      'in')
                                                          ->create();
        $taxClassCollection = $this->taxClassRepository->getList($searchCriteria);

        foreach ($taxClassCollection->getItems() as $item) {

            $this->allTaxClasses[] = $item;
        }
    }

    /**
     * Will create 'empty' groups. Possible groups for Price List x are:
     * - xCore Price List x
     * - xCore Price List x including
     * - xCore Price List x excluding
     *
     * If no customers are linked to price list x with a specific VAT class,
     * the group itself will not have been created. This method will create
     * those non-existing groups. That way, tier prices will also be created
     * for those groups.
     *
     * Benefit:          As soon as a customer is linked to the group, tier
     *                   prices are visible for his/her.
     * Disadvantage:     More tier prices will be created, and there's no
     *                   guarantee a customer will actually be added to the
     *                   group with the specific VAT class.
     */
    private function createGroups()
    {
        $this->logger->info("Creating 'empty' xCore Price List groups");

        $this->setAllGroups();

        $this->setAllTaxClasses();

        $newGroups = 0;

        /** @var PriceListInterface $priceList */
        foreach ($this->allPriceLists as $priceList) {

            /** @var TaxClassInterface $taxClassGroup */
            foreach ($this->allTaxClasses as $taxClassGroup) {

                if ($this->getGroups($priceList->getId(), $taxClassGroup->getClassId()))
                    continue;

                $this->createGroup($priceList, $taxClassGroup);

                $newGroups++;
            }
        }

        if ($newGroups)
            $this->logger->info(sprintf('Created %s new customer group(s)', $newGroups));
    }

    /**
     * Get groups that belong to a specific price list and/or tax class.
     *
     * @param null $priceListId
     * @param null $taxClassId
     * @return CustomerGroup[]
     */
    private function getGroups($priceListId = null, $taxClassId = null)
    {
        $groups = [];

        foreach ($this->allCustomerGroups as $group) {

            if (($priceListId == null || $group->priceListId == $priceListId) && ($taxClassId == null || $group->taxClassId == $taxClassId))
                $groups[] = $group;
        }

        return $groups;
    }

    /**
     * Creates a group and adds it to the database.
     *
     * @param PriceListInterface $priceList
     * @param TaxClassInterface $taxClassGroup
     */
    private function createGroup(PriceListInterface $priceList, TaxClassInterface $taxClassGroup)
    {
        $code = 'xCore Price List ' . $priceList->getId();

        switch ($taxClassGroup->getClassName()) {
            case XcoreTaxClass::INCL_VAT:
                $code .= ' including';
                break;
            case XcoreTaxClass::EXCL_VAT:
                $code .= ' excluding';
                break;
        }

        /** @var Group $group */
        $group = $this->customerGroupFactory->create();
        $group->setCode($code);
        $group->setTaxClassId($taxClassGroup->getClassId());

        $this->customerGroupRepository->save($group);
    }

    private function moduleEnabled()
    {
        $status = $this->helper->getGeneralConfig(GeneralConfig::ENABLED);

        if (!$status)
            $this->logger->info(self::DISABLED_MSG);

        return $status;
    }

    private function createEmptyGroups()
    {
        $bool = $this->helper->getCronConfig(CronConfig::EMPTY_GROUPS);

        return $bool;
    }
}