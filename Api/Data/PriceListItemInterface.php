<?php

namespace Dealer4dealer\Pricelist\Api\Data;

interface PriceListItemInterface
{
    const ID            = 'id';
    const PRICE_LIST_ID = 'price_list_id';
    const PRODUCT_ID    = 'product_id';
    const QTY           = 'qty';
    const PRICE         = 'price';
    const START_DATE    = 'start_date';
    const END_DATE      = 'end_date';

    /**
     * Get id
     * @return string|null
     */
    public function getId();

    /**
     * Set id
     * @param string $id
     * @return PriceListItemInterface
     */
    public function setId($id);

    /**
     * Get price_list_id
     * @return string|null
     */
    public function getPriceListId();

    /**
     * Set price_list_id
     * @param string $price_list_id
     * @return PriceListItemInterface
     */
    public function setPriceListId($price_list_id);

    /**
     * Get product_id
     * @return string|null
     */
    public function getProductId();

    /**
     * Set product_id
     * @param string $product_id
     * @return PriceListItemInterface
     */
    public function setProductId($product_id);

    /**
     * Get qty
     * @return string|null
     */
    public function getQty();

    /**
     * Set qty
     * @param string $qty
     * @return PriceListItemInterface
     */
    public function setQty($qty);

    /**
     * Get price
     * @return string|null
     */
    public function getPrice();

    /**
     * Set price
     * @param string $price
     * @return PriceListItemInterface
     */
    public function setPrice($price);

    /**
     * Get start_date
     * @return string|null
     */
    public function getStartDate();

    /**
     * Set start_date
     * @param string $start_date
     * @return PriceListItemInterface
     */
    public function setStartDate($start_date);

    /**
     * Get end_date
     * @return string|null
     */
    public function getEndDate();

    /**
     * Set end_date
     * @param string $end_date
     * @return PriceListItemInterface
     */
    public function setEndDate($end_date);
}
