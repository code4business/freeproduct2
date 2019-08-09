<?php

namespace C4B\FreeProduct\SalesRule\Action;

use Magento\Framework\DataObject;
use Magento\Quote\Model\Quote;
use Magento\SalesRule\Model\Rule;

/**
 * Handles applying a "Add a Gift" type SalesRule.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class GiftAction extends AbstractGiftAction
{
    /**
     * @inheritDoc
     */
    protected function getAppliedRuleStorage(Quote\Item $item): DataObject
    {
        return $item->getAddress();
    }

    /**
     * @inheritDoc
     */
    protected function getGiftQty(Quote\Item $item, Rule $rule, $qty): float
    {
        return $rule->getDiscountAmount();
    }
}