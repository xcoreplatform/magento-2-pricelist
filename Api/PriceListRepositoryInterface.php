<?php

namespace Dealer4dealer\Pricelist\Api;

use Dealer4dealer\Pricelist\Api\Data\PriceListInterface;
use Dealer4dealer\Pricelist\Api\Data\PriceListSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

interface PriceListRepositoryInterface
{
    /**
     * Save price_list
     * @param PriceListInterface $priceList
     * @return PriceListInterface
     * @throws LocalizedException
     */
    public function save(
        PriceListInterface $priceList
    );

    /**
     * Retrieve price_list
     * @param string $priceListId
     * @return PriceListInterface
     * @throws LocalizedException
     */
    public function getById($priceListId);

    /**
     * Retrieve price_list matching the specified criteria.
     * @param SearchCriteriaInterface $searchCriteria
     * @return PriceListSearchResultsInterface
     * @throws LocalizedException
     */
    public function getList(
        SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete price_list
     * @param PriceListInterface $priceList
     * @return bool true on success
     * @throws LocalizedException
     */
    public function delete(
        PriceListInterface $priceList
    );

    /**
     * Delete price_list by ID
     * @param string $priceListId
     * @return bool true on success
     * @throws NoSuchEntityException
     * @throws LocalizedException
     */
    public function deleteById($priceListId);
}
