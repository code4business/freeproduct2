<?php

namespace C4B\FreeProduct\SalesRule\Action;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Rule;

/**
 * Adds a gift for each cart item that meets criteria. It is also multiplied by the qty of said cart item.
 * Ex. Add one gift for each product from category Sales.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class ForeachGiftAction extends AbstractGiftAction
{
    const ACTION = 'add_gift_foreach';

    /**
     * @inheritDoc
     */
    protected function getAppliedRuleStorage(Quote\Item $item): DataObject
    {
        return $item;
    }

    /**
     * @inheritDoc
     */
    protected function getGiftQty(Quote\Item $item, Rule $rule, $qty): float
    {
        return $rule->getDiscountAmount() * $qty;
    }
}