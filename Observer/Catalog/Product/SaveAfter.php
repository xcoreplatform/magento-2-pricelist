<?php

namespace Dealer4dealer\Pricelist\Observer\Catalog\Product;

use Dealer4dealer\Pricelist\Helper\Codes\ItemConfig;
use Magento\Catalog\Model\Product;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class SaveAfter implements ObserverInterface
{

    private $itemGroup;

    public function __construct(

    ) {

    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->moduleEnabled() == false) {
            return;
        }

        /** @var Product $product */
        $product = $observer->getEvent()->getProduct();

        $itemGroupAttributeCode = $this->helper->getItemConfig(ItemConfig::ITEMGROUP_ATTRIBUTE_CODE);

        $this->itemGroup = $product->getCustomAttribute($itemGroupAttributeCode);
        if ($this->itemGroup) {
            $this->itemGroup = $this->itemGroup->getValue();
        }
    }
}
