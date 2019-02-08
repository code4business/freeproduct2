<?php

namespace C4B\FreeProduct\SalesRule\Action;

use C4B\FreeProduct\Observer\ResetGiftItems;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Model\Address;
use Magento\Quote\Model\Quote\Item\AbstractItem;
use Magento\SalesRule\Model\Rule\Action\Discount;
use Psr\Log\LoggerInterface;

/**
 * Adds a gift for each cart item that meets criteria. It is also multiplied by the qty of said cart item.
 * Ex. Add one gift for each product from category Sales.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class ForeachGiftAction extends GiftAction
{
    const ACTION = 'add_gift_foreach';
    /**
     * @var ResetGiftItems
     */
    private $resetGiftItems;
    /**
     * @var LoggerInterface
     */
    private $logger;


    /**
     * @param Discount\DataFactory $discountDataFactory
     * @param ProductRepositoryInterface $productRepository
     * @param ResetGiftItems $resetGiftItems
     * @param LoggerInterface $logger
     */
    public function __construct(Discount\DataFactory $discountDataFactory,
                                ProductRepositoryInterface $productRepository,
                                ResetGiftItems $resetGiftItems,
                                LoggerInterface $logger)
    {
        parent::__construct($discountDataFactory, $productRepository, $resetGiftItems, $logger);
        $this->resetGiftItems = $resetGiftItems;
        $this->logger = $logger;
    }

    /**
     * @param \Magento\SalesRule\Model\Rule $rule
     * @param AbstractItem $item
     * @param float $qty
     * @return Discount\Data
     */
    public function calculate($rule, $item, $qty)
    {
        $appliedRuleIds = $item->getData(static::APPLIED_FREEPRODUCT_RULE_IDS);

        if ($item->getAddress()->getAddressType() != Address::TYPE_SHIPPING
            || ($appliedRuleIds != null && isset($appliedRuleIds[$rule->getId()])))
        {
            return $this->getDiscountData($item);
        }

        $skus = explode(',', $rule->getData(static::RULE_DATA_KEY_SKU));
        $isRuleAdded = false;

        foreach ($skus as $sku)
        {
            try
            {
                $quoteItem = $item->getQuote()->addProduct($this->getGiftProduct($sku), $rule->getDiscountAmount() * $qty);
                $item->getQuote()->setItemsCount($item->getQuote()->getItemsCount() + 1);
                $item->getQuote()->setItemsQty((float)$item->getQuote()->getItemsQty() + $quoteItem->getQty());
                $this->resetGiftItems->reportGiftItemAdded();

                if (is_string($quoteItem))
                {
                    throw new \Exception($quoteItem);
                }

                $isRuleAdded = true;
            } catch (\Exception $e)
            {
                $this->logger->error(
                    sprintf('Exception occurred while adding gift product %s to cart. Rule: %d, Exception: %s', implode(',', $skus), $rule->getId(), $e->getMessage()),
                    [__METHOD__]
                );
            }
        }
        if ($isRuleAdded)
        {
            $this->addAppliedRuleIdToItem($rule->getRuleId(), $item);
        }

        return $this->getDiscountData($item);
    }

    /**
     * @param int $ruleId
     * @param AbstractItem $quoteItem
     */
    protected function addAppliedRuleIdToItem(int $ruleId, AbstractItem $quoteItem)
    {
        $appliedRules = $quoteItem->getData(static::APPLIED_FREEPRODUCT_RULE_IDS);

        if ($appliedRules == null)
        {
            $appliedRules = [];
        }

        $appliedRules[$ruleId] = $ruleId;

        $quoteItem->setData(static::APPLIED_FREEPRODUCT_RULE_IDS, $appliedRules);
    }
}