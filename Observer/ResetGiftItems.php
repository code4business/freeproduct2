<?php

namespace C4B\FreeProduct\Observer;

use C4B\FreeProduct\SalesRule\Action\ForeachGiftAction;
use C4B\FreeProduct\SalesRule\Action\GiftAction;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;

/**
 * Observer for resetting gift cart items.
 * When quote totals are collected, all gifts are removed and are later re-added by Discount total collector.
 * It is triggered by two events:
 * - quote collect before: for normal quote operations (adding items, changing qty, removing item)
 * - address collect before: When shipping is estimated the above event is not triggered.
 *
 * There is some weird handling of quote items. There are two ways to get them: getItems() and getItemsCollection()
 * New quote items are added into the collection, but not into getItems. This is apparently how it should be because
 * otherwise newly added quote items are added again since they don't have an item_id yet and in case of bundle items this would fail.
 * So quote->setItems should not be used here:
 *      @see \Magento\Quote\Model\QuoteRepository\SaveHandler::save
 *      @see \Magento\Quote\Model\Quote\Item\CartItemPersister::save
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class ResetGiftItems implements ObserverInterface
{
    /**
     * @var bool
     */
    private $areGiftItemsReset = false;

    /**
     * @event sales_quote_collect_totals_before
     * @event sales_quote_address_collect_totals_before
     * @param Observer $observer
     * @return void
     * @throws \Exception
     */
    public function execute(Observer $observer)
    {
        /** @var Quote $quote */
        $quote = $observer->getEvent()->getData('quote');
        /** @var ShippingAssignmentInterface $shippingAssignment */
        $shippingAssignment = $observer->getEvent()->getData('shipping_assignment');

        if ($quote->getItems() == null || $this->areGiftItemsReset)
        {
            return;
        }

        if ($shippingAssignment instanceof ShippingAssignmentInterface)
        {
            /** @var Quote\Address $address */
            $address = $shippingAssignment->getShipping()->getAddress();

            if ($address->getAddressType() != Quote\Address::ADDRESS_TYPE_SHIPPING)
            {
                return;
            }
        }
        else
        {
            $address = $quote->getShippingAddress();
        }

        $realQuoteItems = $this->removeOldGiftQuoteItems($quote->getItemsCollection());
        $this->areGiftItemsReset = true;
        $address->unsetData(GiftAction::APPLIED_FREEPRODUCT_RULE_IDS);
        $address->unsetData('cached_items_all');

        if ($shippingAssignment instanceof ShippingAssignmentInterface)
        {
            $shippingAssignment->setItems($realQuoteItems);
            $this->updateExtensionAttributes($quote, $shippingAssignment);
        }
    }

    /**
     * A new gift item was added so if cart totals are collected again, all gift items will be reset.
     *
     * @return void
     */
    public function reportGiftItemAdded()
    {
        $this->areGiftItemsReset = false;
    }

    /**
     * @param \Magento\Quote\Model\ResourceModel\Quote\Item\Collection|\Magento\Framework\Data\Collection $quoteItemsCollection
     * @return Quote\Item[]
     * @throws \Exception
     */
    protected function removeOldGiftQuoteItems($quoteItemsCollection)
    {
        $realQuoteItems = [];

        /** @var Quote\Item $quoteItem */
        foreach ($quoteItemsCollection->getItems() as $key => $quoteItem)
        {
            if ($quoteItem->isDeleted())
            {
                continue;
            } else if ($quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option)
            {
                $quoteItem->isDeleted(true);

                /**
                 * In some cases when the quoteItem is being deleted its option will be saved. It will fail because item_id
                 * is null.
                 */
                foreach ($quoteItem->getOptions() as $option)
                {
                    $option->isDeleted(true);
                }
            } else
            {
                $quoteItem->unsetData(ForeachGiftAction::APPLIED_FREEPRODUCT_RULE_IDS);
                $realQuoteItems[$key] = $quoteItem;
            }
        }

        return $realQuoteItems;
    }

    /**
     * Update shipping assignments from cart.
     *
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     */
    protected function updateExtensionAttributes(Quote $quote, $shippingAssignment)
    {
        if ($quote->getExtensionAttributes() != null)
        {
            $shippingAssignmentsExtension = $quote->getExtensionAttributes()->getShippingAssignments();

            if ($shippingAssignmentsExtension != null)
            {
                $shippingAssignmentsExtension[0] = $shippingAssignment;
            }
        }
    }
}