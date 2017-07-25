<?php

namespace Dealer4dealer\Pricelist\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PriceList extends AbstractDb
{
    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('dealer4dealer_price_list', 'price_list_id');
    }
}
