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
use Dealer4dealer\Xcore\Api\Data\PriceListItemGroupInterface;
use Dealer4dealer\Xcore\Api\Data\PriceListItemInterface;
use Dealer4dealer\Xcore\Api\PriceListItemGroupRepositoryInterface;
use Dealer4dealer\Xcore\Api\PriceListItemRepositoryInterface;
use Dealer4dealer\Xcore\Api\PriceListRepositoryInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterface;
use Magento\Catalog\Api\Data\ProductTierPriceInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Api\ScopedProductTierPriceManagementInterface;
use Magento\Catalog\Model\Product\ScopedTierPriceManagement;
use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Eav\Model\Config;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
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
    private $filterGroupBuilder;
    private $filterBuilder;
    private $searchCriteriaBuilder;
    private $customerGroupRepository;
    private $customerGroupFactory;
    private $taxClassRepository;
    private $priceListRepository;
    private $priceListItemRepository;
    private $priceListItemGroupRepository;
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
     * @var ProductTierPriceInterface
     */
    private $productTierPriceFactory;
    private $config;
    private $productRepository;

    /**
     * Constructor.
     *
     * @param LoggerInterface                   $logger
     * @param Data                              $helper
     * @param FilterGroupBuilder                $filterGroupBuilder
     * @param FilterBuilder                     $filterBuilder
     * @param SearchCriteriaBuilder             $searchCriteriaBuilder
     * @param GroupRepository                   $groupRepository
     * @param GroupInterfaceFactory|BaseFactory $groupFactory
     * @param TaxClassRepository                $taxRepository
     * @param PriceListRepositoryInterface      $priceListRepository
     * @param PriceListItemRepositoryInterface  $priceListItemRepository
     * @param ScopedTierPriceManagement         $tierPriceManagement
     * @param ProductTierPriceInterfaceFactory  $productTierPriceFactory
     * @param Config                            $config
     * @param ProductRepositoryInterface        $productRepository
     */
    public function __construct(
        LoggerInterface $logger,
        Data $helper,
        FilterGroupBuilder $filterGroupBuilder,
        FilterBuilder $filterBuilder,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        GroupRepository $groupRepository,
        GroupInterfaceFactory $groupFactory,
        TaxClassRepository $taxRepository,
        PriceListRepositoryInterface $priceListRepository,
        PriceListItemRepositoryInterface $priceListItemRepository,
        PriceListItemGroupRepositoryInterface $priceListItemGroupRepository,
        ScopedProductTierPriceManagementInterface $tierPriceManagement,
        ProductTierPriceInterfaceFactory $productTierPriceFactory,
        Config $config,
        ProductRepositoryInterface $productRepository
    ) {
        $this->logger                       = $logger;
        $this->helper                       = $helper;
        $this->filterGroupBuilder           = $filterGroupBuilder;
        $this->filterBuilder                = $filterBuilder;
        $this->searchCriteriaBuilder        = $searchCriteriaBuilder;
        $this->customerGroupRepository      = $groupRepository;
        $this->customerGroupFactory         = $groupFactory;
        $this->taxClassRepository           = $taxRepository;
        $this->priceListRepository          = $priceListRepository;
        $this->priceListItemRepository      = $priceListItemRepository;
        $this->priceListItemGroupRepository = $priceListItemGroupRepository;
        $this->tierPriceManagement          = $tierPriceManagement;
        $this->productTierPriceFactory      = $productTierPriceFactory;
        $this->config                       = $config;
        $this->productRepository            = $productRepository;
    }

    /**
     * Execute the cron
     *
     * @return \Dealer4dealer\Pricelist\Api\Data\CronResultInterface|string
     */
    public function execute()
    {
        if ($this->moduleEnabled() == false) {
            return self::DISABLED_MSG;
        }

        $this->logger->info(self::EXECUTE_MSG);

        $this->setAllLists();

        $this->processCustomerGroupPriceLists();

        $this->processItemGroupPriceLists();

        $result = new CronResult;
        $result->setRemoved($this->removedTierPrices);
        $result->setAddedOrUpdated($this->addedTierPrices);

        $this->logger->info(sprintf(self::COMPLETED_MSG, $result->getRemoved(), $result->getAddedOrUpdated()));

        return $result;
    }

    private function processItemGroupPriceLists()
    {
        /** @var PriceListInterface $priceList */
        foreach ($this->allPriceLists as $priceList) {
            $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                          ->addFilter('price_list_id', $priceList->getId())
                                                          ->create();

            $priceListItemGroups = $this->priceListItemGroupRepository->getList($searchCriteria)

            if (!$priceListItemGroups) {
                $this->logger->info('No Item Groups Found');
                continue;
            }

            /** @var PriceListItemGroupInterface $priceListItemGroup */
            foreach ($priceListItemGroups as $priceListItemGroup) {
                $itemGroupCode            = $priceListItemGroup->getItemGroupCode();
                $itemGroupAttributeValues = $this->config->getAttribute('catalog_product', 'xcore_item_group')->getSource()->getAllOptions();

                $option = null;
                foreach ($itemGroupAttributeValues as $itemGroupAttributeValueKey => $itemGroupAttributeValue) {
                    if ($itemGroupCode === $itemGroupAttributeValue) {
                        $optionId = $itemGroupAttributeValueKey;
                        break;
                    }
                }

                if (!$optionId) {
                    continue;
                }
                $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                              ->addFilter('xcore_item_group', $optionId)
                                                              ->create();
                $products       = $this->productRepository->getList($searchCriteria);

                foreach ($products->getItems() as $product) {
                    $this->createTierPricesForItemGroup($product->getSku(), $priceListItemGroup);
                }
            }
        }
    }

    private function createTierPricesForItemGroup(string $sku, PriceListItemGroupInterface $priceListItemGroup)
    {
        try {
            /** @var ProductTierPriceInterface $productTierPrice */
            $productTierPrice = $this->productTierPriceFactory->create();
            $productTierPrice->setCustomerGroupId('all')
                             ->setQty($priceListItemGroup->getQty());

            $extensionAttributes = $productTierPrice->getExtensionAttributes();
            $extensionAttributes->setPercentageValue($priceListItemGroup->getDiscount());

            $productTierPrice->setExtensionAttributes($extensionAttributes);

            try {
                $this->tierPriceManagement->remove($sku, $productTierPrice);
            } catch (\Exception $exception) {
                // As there's no addOrUpdate, we first try to remove the tier price before adding it.
            }

            $this->tierPriceManagement->add($sku, $productTierPrice);

            $this->addedTierPrices++;

            $priceListItemGroup->setProcessed(1);

            // Reset error count as it was added successfully
            $priceListItemGroup->setErrorCount(0);
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(self::FAILED_ADD_MSG, $sku, 'all'));
            $this->logger->info($exception->getMessage());

            // Up error count as it failed for some reason.
            $errorCount = (int)$priceListItemGroup->getErrorCount() + 1;
            $priceListItemGroup->setErrorCount($errorCount);
        }

        $this->priceListItemGroupRepository->save($priceListItemGroup);
    }

    private function processCustomerGroupPriceLists()
    {
        if ($this->createEmptyGroups()) {
            $this->createGroups();
        }

        $this->setAllGroups();

        $this->setItemsToRemove();

        $this->setItemsToAdd();

        foreach ($this->itemsToProcess as $sku => $process) {
            if (isset($process['remove'])) {
                $this->removeTierPrices($sku, $process['remove']);
            }

            if (isset($process['add'])) {
                $this->createTierPrices($sku, $process['add']);
            }
        }
    }

    /**
     * Removes tier prices.
     *
     * @param string                     $sku
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
     * @param string                   $sku
     * @param CustomerGroup            $group
     * @param PriceListItemInterface[] $priceListItems
     */
    private function removeTierPricesForGroup($sku, $group, $priceListItems)
    {
        $tierPrices = [];

        try {
            $tierPrices = $this->tierPriceManagement->getList($sku, $group->id);
        } catch (\Exception $exception) {
            $this->logger->error(sprintf(self::FAILED_REMOVE_MSG, $sku, $group->id));
            $this->logger->info($exception->getMessage());
        }

        /** @var ProductTierPriceInterface $tierPrice */
        foreach ($tierPrices as $tierPrice) {
            foreach ($priceListItems as $priceListItem) {
                if ($tierPrice->getQty() == $priceListItem->getQty()) {
                    try {
                        $this->tierPriceManagement->remove($sku, $tierPrice);

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
     * @param string                     $sku
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
     * @param string                   $sku
     * @param CustomerGroup            $group
     * @param PriceListItemInterface[] $priceListItems
     */
    private function createTierPricesForGroup($sku, $group, $priceListItems)
    {
        foreach ($priceListItems as $priceListItem) {
            try {
                /** @var ProductTierPriceInterface $productTierPrice */
                $productTierPrice = $this->productTierPriceFactory->create();
                $productTierPrice->setCustomerGroupId($group->id)
                                 ->setQty(floatval($priceListItem->getQty()))
                                 ->setValue(floatval($priceListItem->getPrice()));

                try {
                    $this->tierPriceManagement->remove($sku, $productTierPrice);
                } catch (\Exception $exception) {
                    // As there's no addOrUpdate, we first try to remove the tier price before adding it.
                }

                $this->tierPriceManagement->add($sku, $productTierPrice);

                $this->addedTierPrices++;

                $priceListItem->setProcessed(1);

                // Reset error count as it was added successfully
                $priceListItem->setErrorCount(0);
            } catch (\Exception $exception) {
                $this->logger->error(sprintf(self::FAILED_ADD_MSG, $sku, $group->id));
                $this->logger->info($exception->getMessage());

                // Up error count as it failed for some reason.
                $errorCount = (int)$priceListItem->getErrorCount() + 1;
                $priceListItem->setErrorCount($errorCount);
            }

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
        $processedFilter = $this->filterBuilder
            ->setField(PriceListItemInterface::PROCESSED)
            ->setValue(0)
            ->setConditionType('eq')
            ->create();

        $errorCountFilter = $this->filterBuilder
            ->setField(PriceListItemInterface::ERROR_COUNT)
            ->setValue(3)
            ->setConditionType('lteq')
            ->create();

        $filterGroup = $this->filterGroupBuilder
            ->addFilter($processedFilter)
            ->addFilter($errorCountFilter)
            ->create();

        $this->searchCriteriaBuilder->setFilterGroups([$filterGroup])
                                    ->addFilter(PriceListItemInterface::PRICE_LIST_ID, $this->activePriceListIds, 'in');

        if (($itemsPerRun = $this->itemsPerRun()) > 0) {
            $this->searchCriteriaBuilder->setPageSize($itemsPerRun);
        }

        $searchCriteria = $this->searchCriteriaBuilder->create();

        $itemCollection = $this->priceListItemRepository->getList($searchCriteria);

        foreach ($itemCollection->getItems() as $item) {
            if ($item->getEndDate() != null && $item->getEndDate() < date('Y-m-d')) {
                $this->priceListItemRepository->delete($item);

                $this->removedTierPrices++;

                continue;
            }

            if ($item->getStartDate() != null && $item->getStartDate() > date('Y-m-d')) {
                continue;
            }

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
                                                       ->addFilter('customer_group_code', 'PL %', 'like')
                                                       ->create();
        $groupCollection = $this->customerGroupRepository->getList($searchCriteria);

        foreach ($groupCollection->getItems() as $item) {
            $priceListId = $this->getPriceListIdByCustomerGroupId($item->getCode());

            $group               = new CustomerGroup();
            $group->id           = $item->getId();
            $group->code         = $item->getCode();
            $group->priceListId  = $priceListId;
            $group->taxClassId   = $item->getTaxClassId();
            $group->taxClassName = $item->getTaxClassName();

            $this->allCustomerGroups[] = $group;
        }

        $this->setAllListIdsToCreateTierPricesFor();
    }

    private function getPriceListIdByCustomerGroupId($customerGroupCode)
    {
        if (str_contains($customerGroupCode, ' #')) {
            $customerGroupCode = explode(' #', $customerGroupCode);
            $customerGroupCode = end($customerGroupCode);
        }

        return filter_var($customerGroupCode, FILTER_SANITIZE_NUMBER_INT);
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

        $this->activePriceListIds = rtrim($this->activePriceListIds, ',');
    }

    private function setAllTaxClasses()
    {
        $this->allTaxClasses = [];

        $searchCriteria     = $this->searchCriteriaBuilder->setFilterGroups([])
                                                          ->addFilter(
                                                              'class_name',
                                                              XcoreTaxClass::WITH_VAT . ',' .
                                                              XcoreTaxClass::WITHOUT_VAT,
                                                              'in'
                                                          )
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
     * If no customers are linked to price list x with a specific VAT class,
     * the group itself will not have been created. This method will create
     * those non-existing groups. That way, tier prices will also be created
     * for those groups.
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
                if ($groups = $this->getGroups($priceList->getId(), $taxClassGroup->getClassId())) {
                    foreach ($groups as $group) {
                        if (!str_contains($group->code, $priceList->getCode())) {
                            $this->updateGroup($group, $priceList, $taxClassGroup);
                        }
                    }

                    continue;
                }

                $this->createGroup($priceList, $taxClassGroup);

                $newGroups++;
            }
        }

        if ($newGroups) {
            $this->logger->info(sprintf('Created %s new customer group(s)', $newGroups));
        }
    }

    /**
     * Get groups that belong to a specific price list and/or tax class.
     *
     * @param null $priceListId
     * @param null $taxClassId
     *
     * @return CustomerGroup[]
     */
    private function getGroups($priceListId = null, $taxClassId = null)
    {
        $groups = [];

        foreach ($this->allCustomerGroups as $group) {
            if (($priceListId == null || $group->priceListId == $priceListId) && ($taxClassId == null || $group->taxClassId == $taxClassId)) {
                $groups[] = $group;
            }
        }

        return $groups;
    }

    /**
     * Creates a group and adds it to the database.
     *
     * @param CustomerGroup      $group
     * @param PriceListInterface $priceList
     * @param TaxClassInterface  $taxClassGroup
     */
    private function updateGroup(CustomerGroup $group, PriceListInterface $priceList, TaxClassInterface $taxClassGroup)
    {
        $code = $this->getGroupCode($priceList, $taxClassGroup);

        /** @var Group $group */
        $group = $this->customerGroupRepository->getById($group->id);
        $group->setCode($code);
        $group->setTaxClassId($taxClassGroup->getClassId());

        $this->customerGroupRepository->save($group);
    }

    /**
     * Creates a group and adds it to the database.
     *
     * @param PriceListInterface $priceList
     * @param TaxClassInterface  $taxClassGroup
     */
    private function createGroup(PriceListInterface $priceList, TaxClassInterface $taxClassGroup)
    {
        $code = $this->getGroupCode($priceList, $taxClassGroup);

        /** @var Group $group */
        $group = $this->customerGroupFactory->create();
        $group->setCode($code);
        $group->setTaxClassId($taxClassGroup->getClassId());

        $this->customerGroupRepository->save($group);
    }

    private function getGroupCode(PriceListInterface $priceList, TaxClassInterface $taxClassGroup)
    {
        $taxAddition = '';
        switch ($taxClassGroup->getClassName()) {
            case XcoreTaxClass::WITH_VAT:
                $taxAddition = ' with vat';
                break;
            case XcoreTaxClass::WITHOUT_VAT:
                $taxAddition = ' without vat';
                break;
        }

        return sprintf('PL %s%s #%s', $priceList->getCode(), $taxAddition, $priceList->getId());
    }

    private function moduleEnabled()
    {
        $status = $this->helper->getGeneralConfig(GeneralConfig::ENABLED);

        if (!$status) {
            $this->logger->info(self::DISABLED_MSG);
        }

        return $status;
    }

    private function createEmptyGroups()
    {
        return $this->helper->getCronConfig(CronConfig::EMPTY_GROUPS);
    }

    private function itemsPerRun()
    {
        return $this->helper->getCronConfig(CronConfig::ITEMS_PER_RUN);
    }
}
