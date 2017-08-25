<?php

namespace Dealer4dealer\Pricelist\Api;

interface PriceListCronInterface
{
    /**
     * @return \Dealer4dealer\Pricelist\Model\CronResultInterface|string
     */
    public function execute();
}