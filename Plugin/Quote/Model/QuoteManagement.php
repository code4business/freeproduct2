<?php

namespace C4B\FreeProduct\Plugin\Quote\Model;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * If a quote is submited as an order and totals were collected without saving in between, gift items will have no IDs and will
 * be lost.
 * Quote is therefor saved if it contains gift items that do not have an ID.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class QuoteManagement
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @see \Magento\Quote\Model\QuoteManagement::submit
     * @plugin before
     * @param \Magento\Quote\Model\QuoteManagement $subject
     * @param Quote $quote
     * @param $orderData
     * @return array
     */
    public function beforeSubmit(\Magento\Quote\Model\QuoteManagement $subject, Quote $quote, $orderData = [])
    {
        foreach ($quote->getAllVisibleItems() as $quoteItem)
        {
            if ($quoteItem->getId() == null &&
                $quoteItem->isDeleted() == false &&
                $quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option)
            {
                $this->cartRepository->save($quote);
                break;
            }
        }

        return [$quote, $orderData];
    }
}