<?php

namespace C4B\FreeProduct\Observer;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Model\Quote;
use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Observer for resetting gift cart items
 *
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Aurélien Lavorel <aurelien@lavoweb.net>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class RemoveGiftItems implements ObserverInterface
{
    /** @var CheckoutSession */
    protected $checkoutSession;

    /**
     * @param CheckoutSession $checkoutSession
     */
    public function __construct(CheckoutSession $checkoutSession)
    {
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Delete all gift items. They will be re-added by SalesRule (If possible).
     *
     * @event sales_quote_remove_item
     * @param Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Quote\Model\Quote  */
        $quote = $this->checkoutSession->getQuote();

        /** @var Quote\Item $quoteItem */
        if ($quote && count($quote->getItems())) {
            foreach ($quote->getItems() as $quoteItem) {
                if ($quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option) {
                    $quoteItem->isDeleted(true);

                    /**
                     * In some cases when the quoteItem is being deleted its option will be saved. It will fail because item_id
                     * is null.
                     */
                    foreach ($quoteItem->getOptions() as $option) {
                        $option->isDeleted(true);
                    }
                }
            }
        }
    }
}