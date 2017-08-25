<?php

namespace Dealer4dealer\Pricelist\Model;

interface SettingInterface
{
    /**
     * @return string
     */
    public function getField();

    /**
     * @var string $field
     * @return \Dealer4dealer\Pricelist\Model\SettingInterface
     */
    public function setField($field);

    /**
     * @return int
     */
    public function getValue();

    /**
     * @var int $value
     * @return \Dealer4dealer\Pricelist\Model\SettingInterface
     */
    public function setValue($value);
}