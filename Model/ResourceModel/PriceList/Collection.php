<?php

namespace Dealer4dealer\Pricelist\Model\ResourceModel\PriceList;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(
            'Dealer4dealer\Pricelist\Model\PriceList',
            'Dealer4dealer\Pricelist\Model\ResourceModel\PriceList'
        );
    }
}
