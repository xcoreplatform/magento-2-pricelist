<?php

namespace Dealer4dealer\Pricelist\Observer\Catalog\Product;

use Dealer4dealer\Pricelist\Cron\PriceListCron;
use Dealer4dealer\Pricelist\Helper\Codes\GeneralConfig;
use Dealer4dealer\Pricelist\Helper\Codes\ItemConfig;
use Dealer4dealer\Pricelist\Helper\Data;
use Dealer4dealer\Xcore\Api\Data\PriceListItemGroupInterface;
use Dealer4dealer\Xcore\Api\PriceListItemGroupRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

class SaveAfter implements ObserverInterface
{
    private $itemGroup;
    private $helper;
    private $logger;
    private $cron;
    private $priceListItemGroupRepository;
    private $searchCriteriaBuilder;
    private $itemGroupAttributeCode;
    private $filterBuilder;
    private $filterGroupBuilder;

    /**
     * Constructor.
     *
     * @param Data                                  $helper
     * @param LoggerInterface                       $logger
     * @param PriceListCron                         $cron
     * @param PriceListItemGroupRepositoryInterface $priceListItemGroupRepository
     * @param SearchCriteriaBuilder                 $searchCriteriaBuilder
     * @param FilterBuilder                         $filterBuilder
     * @param FilterGroupBuilder                    $filterGroupBuilder
     */
    public function __construct(
        Data $helper,
        LoggerInterface $logger,
        PriceListCron $cron,
        PriceListItemGroupRepositoryInterface $priceListItemGroupRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder
    ) {
        $this->helper                       = $helper;
        $this->logger                       = $logger;
        $this->cron                         = $cron;
        $this->priceListItemGroupRepository = $priceListItemGroupRepository;
        $this->searchCriteriaBuilder        = $searchCriteriaBuilder;
        $this->filterBuilder                = $filterBuilder;
        $this->filterGroupBuilder           = $filterGroupBuilder;
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

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        $this->itemGroupAttributeCode = $this->helper->getItemConfig(ItemConfig::ITEMGROUP_ATTRIBUTE_CODE);

        $this->itemGroup = $product->getCustomAttribute($this->itemGroupAttributeCode)->getValue();

        //Check if the item group has changed on the product
        if (is_null($this->itemGroup) || ($this->itemGroup === $product->getOrigData()[$this->itemGroupAttributeCode])) {
            return;
        }

        $oldPriceListItemGroups = $this->findPriceListItemGroupByItemGroupId($product->getOrigData()[$this->itemGroupAttributeCode]);
        $newPriceListItemGroups = $this->findPriceListItemGroupByItemGroupId($this->itemGroup);
        

        $this->cron->setUpdateSingleProduct($product->getSku(), $newPriceListItemGroups, $oldPriceListItemGroups);
        $this->cron->execute();
    }

    private function moduleEnabled()
    {
        $status = $this->helper->getGeneralConfig(GeneralConfig::ENABLED);
        if (!$status) {
            $this->logger->info('Dealer4Dealer Price List setting -- Module enabled = false: skipping Observer/Customer/SaveAfter::execute()');
        }

        return $status;
    }

    private function findPriceListItemGroupByItemGroupId($itemGroupId)
    {

//
//        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
//                                                      ->addFilter(PriceListItemGroupInterface::ITEM_GROUP, $itemGroupId)
//                                                      ->addFilter(
//                                                          PriceListItemGroupInterface::END_DATE,
//                                                          [
//                                                              'gt' => date('Y-m-d'),
//                                                              null => true,
//                                                          ],
//                                                          'or'
//                                                      )
//                                                      ->create();


        $searchCriteria = $this->searchCriteriaBuilder->setFilterGroups([])
                                                      ->addFilter(PriceListItemGroupInterface::ITEM_GROUP, $itemGroupId)
                                                      ->create();

        return $this->priceListItemGroupRepository->getList($searchCriteria)->getItems() ?? [];
    }
}
