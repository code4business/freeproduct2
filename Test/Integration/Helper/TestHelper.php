<?php

namespace C4B\FreeProduct\Test\Integration\Helper;

use C4B\FreeProduct\SalesRule\Action\GiftAction;

use Magento\Customer\Model\GroupManagement;
use Magento\Framework\App\ResourceConnection;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\SalesRule\Model\RuleFactory;
use Magento\Store\Model\StoreManagerInterface;

use Magento\TestFramework\Helper\Bootstrap;

/**
 * Test helper containing setup and other helper functions for FreeProductTest
 *
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class TestHelper
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var RuleFactory
     */
    private $ruleFactory;
    /**
     * @var GroupManagement
     */
    private $groupManagement;

    /**
     * @param StoreManagerInterface $storeManager
     * @param RuleFactory $ruleFactory
     * @param GroupManagement $groupManagement
     */
    public function __construct(StoreManagerInterface $storeManager,
                                RuleFactory $ruleFactory,
                                GroupManagement $groupManagement)
    {
        $this->storeManager = $storeManager;
        $this->ruleFactory = $ruleFactory;
        $this->groupManagement = $groupManagement;
    }

    public function cleanupDatabase()
    {
        // Products
        /** @var ResourceConnection $resource */
        $resource = Bootstrap::getObjectManager()->get(ResourceConnection::class);
        $productIds = $resource->getConnection()->fetchAll(
            $resource->getConnection()->select()
                ->from('catalog_product_entity', 'entity_id')
                ->where('sku LIKE "freeproduct%"')
        );
        $resource->getConnection()->delete(
            $resource->getTableName('catalog_product_entity'),
            $resource->getConnection()->quoteInto('entity_id IN (?)', $productIds)
        );

        // URL rewrites (they don't have foreign keys
        $resource->getConnection()->delete(
            $resource->getTableName('url_rewrite'),
            $resource->getConnection()->quoteInto('entity_id IN (?) AND entity_type = "product"', $productIds)
        );

        // SalesRules
        $resource->getConnection()->delete($resource->getTableName('salesrule'), 'name LIKE "Free gift%"');
    }

    /**
     * @param Order|OrderInterface|Quote $salesObject
     * @param string $freeProductSku
     * @return null|Order\Item|Quote\Item
     */
    public function getFreeproductItem($salesObject, string $freeProductSku)
    {
        $freeproductItem = null;

        /** @var Order\Item|Quote\Item $item */
        foreach ($salesObject->getAllItems() as $item)
        {
            if ($item->getProductType() == GiftAction::PRODUCT_TYPE_FREEPRODUCT && $item->getSku() == $freeProductSku)
            {
                $freeproductItem = $item;
                break;
            }
        }

        return $freeproductItem;
    }


    /**
     * @param array $salesRuleSpecification
     * @return \Magento\SalesRule\Model\Rule
     */
    public function createSalesRule(array $salesRuleSpecification): \Magento\SalesRule\Model\Rule
    {
        /** @var \Magento\SalesRule\Model\Rule $salesRule */
        $salesRule = $this->ruleFactory->create(['data' =>
            $salesRuleSpecification + [
                'is_active' => 1,
                'customer_group_ids' => [GroupManagement::NOT_LOGGED_IN_ID, $this->groupManagement->getDefaultGroup()->getId()],
                'simple_action' => GiftAction::ACTION,
                'discount_step' => 0,
                'stop_rules_processing' => 0,
                'website_ids' => [
                    $this->storeManager->getWebsite()->getId()
                ]
            ]
        ]);
        $salesRule->save();

        return $salesRule;
    }
}