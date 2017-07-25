<?php

namespace Dealer4dealer\Pricelist\Model;

use Dealer4dealer\Pricelist\Api\Data\PriceListItemInterface;
use Magento\Framework\Model\AbstractModel;

class PriceListItem extends AbstractModel implements PriceListItemInterface
{

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Dealer4dealer\Pricelist\Model\ResourceModel\PriceListItem');
    }

    /**
     * Get id
     * @return string
     */
    public function getId()
    {
        return $this->getData(self::ID);
    }

    /**
     * Set id
     * @param string $id
     * @return PriceListItemInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get price_list_id
     * @return string
     */
    public function getPriceListId()
    {
        return $this->getData(self::PRICE_LIST_ID);
    }

    /**
     * Set price_list_id
     * @param string $price_list_id
     * @return PriceListItemInterface
     */
    public function setPriceListId($price_list_id)
    {
        return $this->setData(self::PRICE_LIST_ID, $price_list_id);
    }

    /**
     * Get product_id
     * @return string
     */
    public function getProductId()
    {
        return $this->getData(self::PRODUCT_ID);
    }

    /**
     * Set product_id
     * @param string $product_id
     * @return PriceListItemInterface
     */
    public function setProductId($product_id)
    {
        return $this->setData(self::PRODUCT_ID, $product_id);
    }

    /**
     * Get qty
     * @return string
     */
    public function getQty()
    {
        return $this->getData(self::QTY);
    }

    /**
     * Set qty
     * @param string $qty
     * @return PriceListItemInterface
     */
    public function setQty($qty)
    {
        return $this->setData(self::QTY, $qty);
    }

    /**
     * Get price
     * @return string
     */
    public function getPrice()
    {
        return $this->getData(self::PRICE);
    }

    /**
     * Set price
     * @param string $price
     * @return PriceListItemInterface
     */
    public function setPrice($price)
    {
        return $this->setData(self::PRICE, $price);
    }

    /**
     * Get start_date
     * @return string
     */
    public function getStartDate()
    {
        return $this->getData(self::START_DATE);
    }

    /**
     * Set start_date
     * @param string $start_date
     * @return PriceListItemInterface
     */
    public function setStartDate($start_date)
    {
        return $this->setData(self::START_DATE, $start_date);
    }

    /**
     * Get end_date
     * @return string
     */
    public function getEndDate()
    {
        return $this->getData(self::END_DATE);
    }

    /**
     * Set end_date
     * @param string $end_date
     * @return PriceListItemInterface
     */
    public function setEndDate($end_date)
    {
        return $this->setData(self::END_DATE, $end_date);
    }
}
