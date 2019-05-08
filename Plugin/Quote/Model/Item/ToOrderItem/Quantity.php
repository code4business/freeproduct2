<?php

namespace C4B\FreeProduct\Plugin\Quote\Model\Item\ToOrderItem;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use \Magento\Quote\Model\Quote\Item\ToOrderItem as Source;

/**
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Aurélien Lavorel <aurelien@lavoweb.net>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Quantity
{
    /**
     * Force qty for gift items
     *
     * @plugin after
     * @param Source $subject
     * @param $result
     * @return $result
     */
    public function afterConvert(Source $subject, $result)
    {
        if ($result->getData('product_type') == GiftAction::PRODUCT_TYPE_FREEPRODUCT) {
            if ($result->getQtyOrdered() > 1) {
                $result->setQtyOrdered($result->getQtyOrdered() / 2);
            }
        }
        return $result;
    }
}