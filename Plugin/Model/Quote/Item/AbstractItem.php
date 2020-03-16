<?php

namespace C4B\FreeProduct\Plugin\Model\Quote\Item;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

/**
 * In rare cases (for example when the order runs through an ERP) is is nice to have the original_price
 * of a free gift item available.
 *
 * Also allows to see the original price in the backend and is good for statistics.
 *
 * That is why we set it here.
 *
 * @package C4B\FreeProduct\Plugin\Model\Quote\Item
 */
class AbstractItem
{
    public function aroundGetOriginalPrice(\Magento\Quote\Model\Quote\Item\AbstractItem $subject, callable $proceed)
    {
        if ($subject->getProductType() == GiftAction::PRODUCT_TYPE_FREEPRODUCT) {
            return $subject->getProduct()->getPrice();
        }

        return $proceed();
    }
}