<?php

namespace Dealer4dealer\Pricelist\Api;

interface HelperDataInterface
{
    /**
     * @param string $code
     * @return int
     */
    public function getGeneralConfig($code);

    /**
     * @param string $code
     * @return int
     */
    public function getCustomerConfig($code);

    /**
     * @param string $code
     * @return int
     */
    public function getCronConfig($code);

    /**
     * @return \Dealer4dealer\Pricelist\Model\SettingInterface[]
     */
    public function getAll();
}