<?php

namespace Dealer4dealer\Pricelist\Api\Data;

interface SettingInterface
{
    /**
     * @return string
     */
    public function getField();

    /**
     * @var string $field
     * @return \Dealer4dealer\Pricelist\Api\Data\SettingInterface
     */
    public function setField($field);

    /**
     * @return string
     */
    public function getValue();

    /**
     * @var string $value
     * @return \Dealer4dealer\Pricelist\Api\Data\SettingInterface
     */
    public function setValue($value);
}