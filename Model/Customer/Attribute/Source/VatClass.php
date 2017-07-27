<?php

namespace Dealer4dealer\Pricelist\Model\Customer\Attribute\Source;

use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;

class VatClass extends AbstractSource
{

    /**
     * getAllOptions
     *
     * @return array
     */
    public function getAllOptions()
    {
        if ($this->_options === null) {
            $this->_options = [
                [
                    'value' => null,
                    'label' => 'No Tax'
                ],
                [
                    'value' => 'excl',
                    'label' => 'Excluding Tax'
                ],
                [
                    'value' => 'incl',
                    'label' => 'Including Tax'
                ]
            ];
        }
        return $this->_options;
    }
}