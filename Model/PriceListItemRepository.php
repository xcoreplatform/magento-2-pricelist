<?php

namespace Dealer4dealer\Pricelist\Model;

use Dealer4dealer\Pricelist\Api\Data\PriceListItemInterface;
use Dealer4dealer\Pricelist\Api\Data\PriceListItemInterfaceFactory;
use Dealer4dealer\Pricelist\Api\Data\PriceListItemSearchResultsInterfaceFactory;
use Dealer4dealer\Pricelist\Api\PriceListItemRepositoryInterface;
use Dealer4dealer\Pricelist\Model\ResourceModel\PriceListItem as ResourcePriceListItem;
use Dealer4dealer\Pricelist\Model\ResourceModel\PriceListItem\CollectionFactory as PriceListItemCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class PriceListItemRepository implements PriceListItemRepositoryInterface
{
    protected $resource;
    protected $priceListItemFactory;
    protected $dataPriceListItemFactory;
    protected $priceListItemCollectionFactory;
    protected $searchResultsFactory;
    protected $dataObjectHelper;
    protected $dataObjectProcessor;

    private $storeManager;

    /**
     * @param ResourcePriceListItem $resource
     * @param PriceListItemFactory $priceListItemFactory
     * @param PriceListItemInterfaceFactory $dataPriceListItemFactory
     * @param PriceListItemCollectionFactory $priceListItemCollectionFactory
     * @param PriceListItemSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourcePriceListItem $resource,
        PriceListItemFactory $priceListItemFactory,
        PriceListItemInterfaceFactory $dataPriceListItemFactory,
        PriceListItemCollectionFactory $priceListItemCollectionFactory,
        PriceListItemSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    )
    {
        $this->resource                       = $resource;
        $this->priceListItemFactory           = $priceListItemFactory;
        $this->priceListItemCollectionFactory = $priceListItemCollectionFactory;
        $this->searchResultsFactory           = $searchResultsFactory;
        $this->dataObjectHelper               = $dataObjectHelper;
        $this->dataPriceListItemFactory       = $dataPriceListItemFactory;
        $this->dataObjectProcessor            = $dataObjectProcessor;
        $this->storeManager                   = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        PriceListItemInterface $priceListItem
    )
    {
        /* if (empty($priceListItem->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $priceListItem->setStoreId($storeId);
        } */
        try {
            $priceListItem->getResource()->save($priceListItem);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                                                'Could not save the priceListItem: %1',
                                                $exception->getMessage()
                                            ));
        }
        return $priceListItem;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($priceListItemId)
    {
        $priceListItem = $this->priceListItemFactory->create();
        $priceListItem->getResource()->load($priceListItem, $priceListItemId);
        if (!$priceListItem->getId()) {
            throw new NoSuchEntityException(__('price_list_item with id "%1" does not exist.', $priceListItemId));
        }
        return $priceListItem;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->priceListItemCollectionFactory->create();
        foreach ($criteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                if ($filter->getField() === 'store_id') {
                    $collection->addStoreFilter($filter->getValue(), false);
                    continue;
                }
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $sortOrders = $criteria->getSortOrders();
        if ($sortOrders) {
            /** @var SortOrder $sortOrder */
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder(
                    $sortOrder->getField(),
                    ($sortOrder->getDirection() == SortOrder::SORT_ASC) ? 'ASC' : 'DESC'
                );
            }
        }
        $collection->setCurPage($criteria->getCurrentPage());
        $collection->setPageSize($criteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(
        PriceListItemInterface $priceListItem
    )
    {
        try {
            $priceListItem->getResource()->delete($priceListItem);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                                                  'Could not delete the price_list_item: %1',
                                                  $exception->getMessage()
                                              ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($priceListItemId)
    {
        return $this->delete($this->getById($priceListItemId));
    }
}
