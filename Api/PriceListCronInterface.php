<?php

namespace Dealer4dealer\Pricelist\Api;

interface PriceListCronInterface
{
    /**
     * @return \Dealer4dealer\Pricelist\Api\Data\CronResultInterface|string
     */
    public function execute();
}