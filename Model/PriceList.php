<?php

namespace Dealer4dealer\Pricelist\Model;

use Dealer4dealer\Pricelist\Api\Data\PriceListInterface;
use Magento\Framework\Model\AbstractModel;

class PriceList extends AbstractModel implements PriceListInterface
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Dealer4dealer\Pricelist\Model\ResourceModel\PriceList');
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
     * @return PriceListInterface
     */
    public function setId($id)
    {
        return $this->setData(self::ID, $id);
    }

    /**
     * Get guid
     * @return string
     */
    public function getGuid()
    {
        return $this->getData(self::GUID);
    }

    /**
     * Set guid
     * @param string $guid
     * @return PriceListInterface
     */
    public function setGuid($guid)
    {
        return $this->setData(self::GUID, $guid);
    }

    /**
     * Get code
     * @return string
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Set code
     * @param string $code
     * @return PriceListInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }
}
