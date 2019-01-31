<?php

namespace C4B\FreeProduct\Plugin\SalesRule\Model;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use C4B\FreeProduct\SalesRule\Action\ForeachGiftAction;

use \Magento\SalesRule\Model\Rule\Metadata\ValueProvider as Source;

/**
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class MetadataValueProvider
{
    /**
     * Add the Gift action option to SalesRule
     *
     * @see \Magento\SalesRule\Model\Rule\Metadata\ValueProvider::getMetadataValues
     * @plugin after
     * @param Source $subject
     * @param array $resultMetadataValues
     * @return array
     */
    public function afterGetMetadataValues(Source $subject, $resultMetadataValues)
    {
        $resultMetadataValues['actions']['children']['simple_action']['arguments']['data']['config']['options'][] = [
            'label' => __('Add a Gift'), 'value' =>  GiftAction::ACTION
        ];
        $resultMetadataValues['actions']['children']['simple_action']['arguments']['data']['config']['options'][] = [
            'label' => __('Add a Gift (For each cart item)'), 'value' =>  ForeachGiftAction::ACTION
        ];

        return $resultMetadataValues;
    }
}