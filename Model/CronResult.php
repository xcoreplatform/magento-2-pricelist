<?php

namespace Dealer4dealer\Pricelist\Model;

class CronResult implements CronResultInterface
{
    protected $removed;
    protected $added;

    /**
     * @return int
     */
    public function getRemoved()
    {
        return $this->removed;
    }

    /**
     * @var int $removed
     * @return $this
     */
    public function setRemoved($removed)
    {
        $this->removed = $removed;
    }

    /**
     * @return int
     */
    public function getAddedOrUpdated()
    {
        return $this->added;
    }

    /**
     * @var int $added
     * @return $this
     */
    public function setAddedOrUpdated($added)
    {
        $this->added = $added;
    }
}