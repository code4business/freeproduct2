<?php

namespace C4B\FreeProduct\Plugin\Quote;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

/**
 * Reset product type back to original when quote item gets converted to order item.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class ResetProductType
{
    /**
     * @param \Magento\Quote\Model\Quote\Item\ToOrderItem $subject
     * @param \Magento\Sales\Model\Order\Item $resultOrderItem
     * @param \Magento\Quote\Model\Quote\Item|\Magento\Quote\Model\Quote\Address\Item $item
     * @param array $data
     * @return \Magento\Sales\Model\Order\Item
     * @plugin after
     * @see \Magento\Quote\Model\Quote\Item\ToOrderItem::convert
     */
    public function afterConvert(\Magento\Quote\Model\Quote\Item\ToOrderItem $subject, $resultOrderItem, $item, $data)
    {
        if ($resultOrderItem->getProductType() == GiftAction::PRODUCT_TYPE_FREEPRODUCT)
        {
            $resultOrderItem->setProductType($item->getRealProductType());
        }

        return $resultOrderItem;
    }
}