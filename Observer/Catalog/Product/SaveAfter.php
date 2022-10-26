<?php

namespace Dealer4dealer\Pricelist\Observer\Catalog\Product;

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

        $this->itemGroup = $product->getCustomAttribute('xcore_item_group');
        if ($this->itemGroup) {
            $this->itemGroup = $this->itemGroup->getValue();
        }
    }
}
