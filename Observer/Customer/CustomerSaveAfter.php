<?php

namespace Dealer4dealer\Pricelist\Observer\Customer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class CustomerSaveAfter implements ObserverInterface
{
    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    )
    {
        //Your observer code
    }
}
