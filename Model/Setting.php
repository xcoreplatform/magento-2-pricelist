<?php

namespace Dealer4dealer\Pricelist\Model;

class Setting implements SettingInterface
{
    protected $field;
    protected $value;

    /**
     * @return int
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @var string $field
     * @return $this
     */
    public function setField($field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @var int $value
     * @return $this
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }
}