<?php

namespace Dealer4dealer\Pricelist\Model;

interface CronResultInterface
{
    /**
     * @return int
     */
    public function getRemoved();

    /**
     * @var int $removed
     * @return \Dealer4dealer\Pricelist\Model\CronResultInterface
     */
    public function setRemoved($removed);

    /**
     * @return int
     */
    public function getAddedOrUpdated();

    /**
     * @var int $added
     * @return \Dealer4dealer\Pricelist\Model\CronResultInterface
     */
    public function setAddedOrUpdated($added);
}