<?php

namespace C4B\FreeProduct\SalesRule\Validator;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

/**
 * Validator for checking if item is valid to be used in SalesRules. Gifts added by freeproduct are not to be processed.
 *
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class GiftItemExcluder implements \Zend_Validate_Interface
{
    /**
     * Gift items should not be processed by SalesRules
     *
     * @param \Magento\Quote\Model\Quote\Item\AbstractItem $item
     * @return bool
     */
    public function isValid($item)
    {
        return $item->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) == null;
    }

    /**
     * @inheritdoc
     */
    public function getMessages()
    {
        return [];
    }
}