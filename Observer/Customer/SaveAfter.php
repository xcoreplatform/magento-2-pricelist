<?php

namespace Dealer4dealer\Pricelist\Observer\Customer;

use Magento\Customer\Api\Data\GroupInterfaceFactory;
use Magento\Customer\Model\Data\Customer;
use Magento\Customer\Model\Data\Group;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Customer\Model\ResourceModel\GroupRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Tax\Model\ClassModel;
use Magento\Tax\Model\TaxClass\Repository as TaxClassRepository;
use Psr\Log\LoggerInterface;

class SaveAfter implements ObserverInterface
{
    protected $logger;
    protected $searchCriteriaBuilder;
    protected $customerRepository;
    protected $customerGroupRepository;
    protected $customerGroupFactory;
    protected $taxClassRepository;

    private $_customerPriceList;
    private $_customerVatClass;
    private $_customerGroupId;

    private $_groupCode;
    private $_groupId;
    private $_taxClassId;

    public function __construct(LoggerInterface $logger,
                                SearchCriteriaBuilder $searchCriteriaBuilder,
                                CustomerRepository $customerRepository,
                                GroupRepository $groupRepository,
                                GroupInterfaceFactory $groupFactory,
                                TaxClassRepository $taxRepository)
    {
        $this->logger                  = $logger;
        $this->searchCriteriaBuilder   = $searchCriteriaBuilder;
        $this->customerRepository      = $customerRepository;
        $this->customerGroupRepository = $groupRepository;
        $this->customerGroupFactory    = $groupFactory;
        $this->taxClassRepository      = $taxRepository;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        /** @var Customer $customer */
        $customer = $observer->getData('customer_data_object');

        $this->_customerPriceList = $customer->getCustomAttribute('price_list');
        if ($this->_customerPriceList) $this->_customerPriceList = $this->_customerPriceList->getValue();

        $this->_customerVatClass = $customer->getCustomAttribute('vat_class');
        if ($this->_customerVatClass) $this->_customerVatClass = $this->_customerVatClass->getValue();

        $this->_customerGroupId = $customer->getGroupId();

        // If a price list is given, make sure the user is added the the correct group
        if ($this->_customerPriceList) {
            $this->createGroupCode();

            $this->createGroupIfNeeded();


            if ($this->_customerGroupId != $this->_groupId) {
                $customer->setGroupId($this->_groupId);

                $this->customerRepository->save($customer);
            }
        }

        if (!$this->_customerPriceList) {
            if ($this->_customerGroupId != 1) {
                $customer->setGroupId(1);

                $this->customerRepository->save($customer);
            }
        }
    }

    /**
     * Generates a group code based on price list id and vat class.
     */
    private function createGroupCode()
    {
        // Create the group code
        $this->_groupCode = 'xCore Price List ' . $this->_customerPriceList;

        // Add the vat class to the code
        if ($this->_customerVatClass) $this->_groupCode .= ' ' . $this->_customerVatClass;
    }

    private function createGroupIfNeeded()
    {
        // Find the group
        $searchForGroup = $this->searchForGroup();

        // If the group has been found, return ($this->_groupId has been set)
        if ($searchForGroup) return;

        // Otherwise, set the tax class id needed for creating the group
        $this->setTaxClassId();

        // Create the group ($this->_groupId will be set)
        $this->createGroup();
    }

    /**
     * Finds the group with the global var $_groupCode
     * and set the $_groupId if a group has been found.
     *
     * @return bool
     */
    private function searchForGroup()
    {
        // Find the group
        $searchCriteria  = $this->searchCriteriaBuilder->setFilterGroups([])
                                                       ->addFilter('customer_group_code', $this->_groupCode)
                                                       ->create();
        $groupCollection = $this->customerGroupRepository->getList($searchCriteria);

        // If the group has been found, set the $this->_groupId and return true
        if ($groupCollection->getItems()) {
            $this->_groupId = $groupCollection->getItems()[0]->getId();
            return true;
        }

        return false;
    }

    /**
     * Sets the global var $_taxClassId. The Tax Classes have been
     * added to the tax_class table on installation of this module.
     */
    private function setTaxClassId()
    {
        $className = null;

        // Set the class name based on the vat class
        switch ($this->_customerVatClass) {
            case null:
                $className = 'xCore No VAT';
                break;
            case 'incl':
                $className = 'xCore Incl VAT';
                break;
            case 'excl':
                $className = 'xCore Excl VAT';
                break;
            default:
                throw new \Exception('No match on customer VAT class.');
        }

        // Find the tax class
        $searchCriteria     = $this->searchCriteriaBuilder->setFilterGroups([])
                                                          ->addFilter('class_name', $className)
                                                          ->create();
        $taxClassCollection = $this->taxClassRepository->getList($searchCriteria);

        // The tax class should be found, set the $this->_taxClassId and return
        if ($taxClassCollection->getItems()) {
            /** @var ClassModel $model */
            $model             = array_values($taxClassCollection->getItems())[0];
            $this->_taxClassId = $model->getClassId();
            return;
        }

        throw new \Exception(sprintf('Tax Class with name %s not found', $className));
    }

    /**
     * Create the customer_group and set $_groupId
     * to the id of the group created in this method.
     */
    private function createGroup()
    {
        /** @var Group $group */
        $group = $this->customerGroupFactory->create();
        $group->setCode($this->_groupCode);
        $group->setTaxClassId($this->_taxClassId);

        // Save the group
        $newGroup = $this->customerGroupRepository->save($group);

        // Set the $this->_groupId to that of the newly created group
        $this->_groupId = $newGroup->getId();
    }
}
