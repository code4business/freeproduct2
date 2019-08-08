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
 * @see        \Magento\Quote\Model\QuoteRepository\SaveHandler::save
 * @see        \Magento\Quote\Model\Quote\Item\CartItemPersister::save
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
        } else
        {
            $address = $quote->getShippingAddress();
        }

        $realQuoteItems = $this->removeOldGiftQuoteItems($quote);

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
     * @param Quote $quote
     * @return Quote\Item[]
     */
    protected function removeOldGiftQuoteItems(Quote $quote)
    {
        $realQuoteItems = [];
        $deletedItemsIds = [];

        /** @var Quote\Item $quoteItem */
        foreach ($quote->getItemsCollection()->getItems() as $key => $quoteItem)
        {
            if ($quoteItem->isDeleted())
            {
                continue;
            } else if ($quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option)
            {
                $quoteItem->isDeleted(true);
                $deletedItemsIds[$quoteItem->getItemId()] = $quoteItem->getItemId();

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

        $this->removeDeletedItemsFromQuoteItems($quote, $deletedItemsIds);
        $this->removeDeletedItemsFromShippingAssignment($quote, $deletedItemsIds);

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

    /**
     * Remove deleted items from $quote->getItems. This list is not the same as $quote->getItemCollection and
     * it should not be because of some weird logic that will make products qty 2x what it should be.
     * @param Quote $quote
     * @param int[] $deletedItemIds
     * @see \Magento\Quote\Model\QuoteRepository\SaveHandler::save
     * @see \Magento\Quote\Model\Quote\Item\CartItemPersister::save
     *
     */
    protected function removeDeletedItemsFromQuoteItems(Quote $quote, array $deletedItemIds): void
    {
        $quote->setItems(
            $this->filterItemsNotInList(
                $quote->getItems(),
                $deletedItemIds
            )
        );
    }

    /**
     * Explicitly remove deleted items from shipping assignment.
     * These items state may not be up to date.
     *
     * @param Quote $quote
     * @param int[] $deletedItemIds
     * @see \Magento\Quote\Model\QuoteRepository\SaveHandler::processShippingAssignment
     */
    protected function removeDeletedItemsFromShippingAssignment(Quote $quote, array $deletedItemIds): void
    {
        $extensionAttributes = $quote->getExtensionAttributes();

        if (!$quote->isVirtual() && $extensionAttributes && $extensionAttributes->getShippingAssignments())
        {
            /** @var \Magento\Quote\Model\ShippingAssignment[] $shippingAssignments */
            $shippingAssignments = $extensionAttributes->getShippingAssignments();
            $shippingAssignments[0]->setItems(
                $this->filterItemsNotInList(
                    $shippingAssignments[0]->getItems(),
                    $deletedItemIds
                )
            );
        }
    }

    /**
     * Remove items that are not in given list
     *
     * @param Quote\Item[] $items
     * @param int[] $itemsToRemove
     * @return Quote\Item[]
     */
    private function filterItemsNotInList(array $items, array $itemsToRemove): array
    {
        $filteredItems = [];

        foreach ($items as $quoteItem)
        {
            if (isset($itemsToRemove[$quoteItem->getItemId()]) == false)
            {
                $filteredItems[] = $quoteItem;
            }
        }

        return $filteredItems;
    }
}