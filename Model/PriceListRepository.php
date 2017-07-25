<?php

namespace Dealer4dealer\Pricelist\Model;

use Dealer4dealer\Pricelist\Api\Data\PriceListInterfaceFactory;
use Dealer4dealer\Pricelist\Api\Data\PriceListSearchResultsInterfaceFactory;
use Dealer4dealer\Pricelist\Api\PriceListRepositoryInterface;
use Dealer4dealer\Pricelist\Model\ResourceModel\PriceList as ResourcePriceList;
use Dealer4dealer\Pricelist\Model\ResourceModel\PriceList\CollectionFactory as PriceListCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;

class PriceListRepository implements PriceListRepositoryInterface
{
    protected $resource;
    protected $priceListFactory;
    protected $dataPriceListFactory;
    protected $priceListCollectionFactory;
    protected $searchResultsFactory;
    protected $dataObjectHelper;
    protected $dataObjectProcessor;

    private $storeManager;

    /**
     * @param ResourcePriceList $resource
     * @param PriceListFactory $priceListFactory
     * @param PriceListInterfaceFactory $dataPriceListFactory
     * @param PriceListCollectionFactory $priceListCollectionFactory
     * @param PriceListSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourcePriceList $resource,
        PriceListFactory $priceListFactory,
        PriceListInterfaceFactory $dataPriceListFactory,
        PriceListCollectionFactory $priceListCollectionFactory,
        PriceListSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager
    )
    {
        $this->resource                   = $resource;
        $this->priceListFactory           = $priceListFactory;
        $this->priceListCollectionFactory = $priceListCollectionFactory;
        $this->searchResultsFactory       = $searchResultsFactory;
        $this->dataObjectHelper           = $dataObjectHelper;
        $this->dataPriceListFactory       = $dataPriceListFactory;
        $this->dataObjectProcessor        = $dataObjectProcessor;
        $this->storeManager               = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        \Dealer4dealer\Pricelist\Api\Data\PriceListInterface $priceList
    )
    {
        /* if (empty($priceList->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $priceList->setStoreId($storeId);
        } */
        try {
            $priceList->getResource()->save($priceList);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                                                'Could not save the priceList: %1',
                                                $exception->getMessage()
                                            ));
        }
        return $priceList;
    }

    /**
     * {@inheritdoc}
     */
    public function getById($priceListId)
    {
        $priceList = $this->priceListFactory->create();
        $priceList->getResource()->load($priceList, $priceListId);
        if (!$priceList->getId()) {
            throw new NoSuchEntityException(__('price_list with id "%1" does not exist.', $priceListId));
        }
        return $priceList;
    }

    /**
     * {@inheritdoc}
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    )
    {
        $collection = $this->priceListCollectionFactory->create();
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
        \Dealer4dealer\Pricelist\Api\Data\PriceListInterface $priceList
    )
    {
        try {
            $priceList->getResource()->delete($priceList);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                                                  'Could not delete the price_list: %1',
                                                  $exception->getMessage()
                                              ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($priceListId)
    {
        return $this->delete($this->getById($priceListId));
    }
}
