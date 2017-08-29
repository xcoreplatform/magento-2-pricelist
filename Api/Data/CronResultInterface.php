<?php

namespace Dealer4dealer\Pricelist\Api\Data;

interface CronResultInterface
{
    /**
     * @return int
     */
    public function getRemoved();

    /**
     * @var int $removed
     * @return \Dealer4dealer\Pricelist\Api\Data\CronResultInterface
     */
    public function setRemoved($removed);

    /**
     * @return int
     */
    public function getAddedOrUpdated();

    /**
     * @var int $added
     * @return \Dealer4dealer\Pricelist\Api\Data\CronResultInterface
     */
    public function setAddedOrUpdated($added);
}