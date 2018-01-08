<?php

namespace C4B\FreeProduct\Test\Integration;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use Magento\TestFramework\Helper\Bootstrap;

use TddWizard\Fixtures as TddWizard;
use PHPUnit\Framework\TestCase;

/**
 * Tests that salesrule is able to add gift products
 *
 * @category   C4B
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class FreeProductTest extends TestCase
{
    /**
     * @var Helper\TestHelper
     */
    private $testHelper;

    /**
     * Remove any products and sales rules from previous failed runs
     */
    protected function setUp()
    {
        $this->testHelper = Bootstrap::getObjectManager()->get(Helper\TestHelper::class);
        $this->testHelper->cleanupDatabase();
    }

    /**
     *  Remove added products and sales rules
     */
    protected function tearDown()
    {
        $this->testHelper->cleanupDatabase();
    }

    /**
     * A gift product is added if the rule matches. The gift product has a price of 0 and correct qty.
     *
     * @test
     * @magentoAppIsolation enabled
     */
    public function testOneGiftAdded()
    {
        $this->testHelper->createSalesRule([
            'name' => 'Free gift over 100€',
            'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
            'conditions' => [
                [
                    'type' => 'Magento\SalesRule\Model\Rule\Condition\Address',
                    'attribute' => 'base_subtotal',
                    'operator' => '>',
                    'value' => 100
                ]
            ],
            'discount_amount' => 2,
            GiftAction::RULE_DATA_KEY_SKU => 'freeproduct-1'
        ]);

        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('freeproduct-1')->withPrice(50)->build();
        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('FreeProductTest-1')->withPrice(150)->build();

        $cart = TddWizard\Checkout\CartBuilder::forCurrentSession()->withSimpleProduct('FreeProductTest-1', 5)->build();

        $freeproductItem = $this->testHelper->getFreeproductItem($cart->getQuote(), 'freeproduct-1');

        $this->assertTrue($freeproductItem !== null, 'Quote does not contain Freeproduct item');
        $this->assertEquals(0, $freeproductItem->getPrice(), 'Price is not zero');
        $this->assertEquals(2, $freeproductItem->getQty(), 'Incorrect gift count was added.');
    }

    /**
     * Gift items can also be normal items. They must not merge while in cart.
     *
     * @test
     * @magentoAppIsolation enabled
     */
    public function testGiftAndRealItemAreNotMerged()
    {
        $this->testHelper->createSalesRule([
            'name' => 'Free gift over 400€',
            'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
            'conditions' => [
                [
                    'type' => 'Magento\SalesRule\Model\Rule\Condition\Address',
                    'attribute' => 'base_subtotal',
                    'operator' => '>',
                    'value' => 399
                ]
            ],
            'discount_amount' => 1,
            GiftAction::RULE_DATA_KEY_SKU => 'freeproduct-2'
        ]);

        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('freeproduct-2')->withPrice(50)->build();
        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('FreeProductTest-2')->withPrice(100)->build();

        $cart = TddWizard\Checkout\CartBuilder::forCurrentSession()
            ->withSimpleProduct('FreeProductTest-2', 4)->withSimpleProduct('freeproduct-2', 1)
            ->build();

        $freeproductItem = $this->testHelper->getFreeproductItem($cart->getQuote(), 'freeproduct-2');

        $this->assertTrue($freeproductItem !== null, 'Quote does not contain Freeproduct item');
        $this->assertEquals(0, $freeproductItem->getPrice(), 'Price is not zero');
        $this->assertEquals(1, $freeproductItem->getQty(), 'Incorrect gift qty');
    }

    /**
     * Multiple rules can work at the same time.
     *
     * @test
     * @magentoAppIsolation enabled
     */
    public function testMultipleRules()
    {
        $this->testHelper->createSalesRule([
            'name' => 'Free gift 1',
            'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
            'conditions' => [
                [
                    'type' => 'Magento\SalesRule\Model\Rule\Condition\Address',
                    'attribute' => 'base_subtotal',
                    'operator' => '>',
                    'value' => 50
                ]
            ],
            'discount_amount' => 1,
            GiftAction::RULE_DATA_KEY_SKU => 'freeproduct-3'
        ]);

        $this->testHelper->createSalesRule([
            'name' => 'Free gift 2',
            'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
            'conditions' => [
                [
                    'type' => 'Magento\SalesRule\Model\Rule\Condition\Address',
                    'attribute' => 'base_subtotal',
                    'operator' => '>',
                    'value' => 50
                ]
            ],
            'discount_amount' => 1,
            GiftAction::RULE_DATA_KEY_SKU => 'freeproduct-4'
        ]);

        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('freeproduct-3')->withPrice(50)->build();
        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('freeproduct-4')->withPrice(50)->build();
        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('FreeProductTest-3')->withPrice(100)->build();

        $cart = TddWizard\Checkout\CartBuilder::forCurrentSession()->withSimpleProduct('FreeProductTest-3', 4)->build();

        $freeproductItem1 = $this->testHelper->getFreeproductItem($cart->getQuote(), 'freeproduct-3');
        $freeproductItem2 = $this->testHelper->getFreeproductItem($cart->getQuote(), 'freeproduct-4');

        $this->assertTrue($freeproductItem1 !== null, 'Quote does not contain Freeproduct item for rule 1');
        $this->assertTrue($freeproductItem2 !== null, 'Quote does not contain Freeproduct item for rule 2');
    }

    /**
     * Order can be placed with freeproduct gift item
     *
     * @test
     * @magentoAppIsolation enabled
     * @magentoConfigFixture default/carriers/flatrate/active 1
     * @magentoConfigFixture default/payment/checkmo/active 1
     */
    public function testPlaceOrder()
    {
        $this->testHelper->createSalesRule([
            'name' => 'Free gift 3',
            'coupon_type' => \Magento\SalesRule\Model\Rule::COUPON_TYPE_NO_COUPON,
            'conditions' => [
                [
                    'type' => 'Magento\SalesRule\Model\Rule\Condition\Address',
                    'attribute' => 'base_subtotal',
                    'operator' => '>',
                    'value' => 50
                ]
            ],
            'discount_amount' => 1,
            GiftAction::RULE_DATA_KEY_SKU => 'freeproduct-5'
        ]);

        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('freeproduct-5')->withPrice(50)->build();
        TddWizard\Catalog\ProductBuilder::aSimpleProduct()->withSku('FreeProductTest-4')->withPrice(100)->build();
        $customerFixture = new TddWizard\Customer\CustomerFixture(TddWizard\Customer\CustomerBuilder::aCustomer()
            ->withAddresses(
                TddWizard\Customer\AddressBuilder::anAddress()->withCountryId('DE')->asDefaultBilling()->asDefaultShipping())
            ->build());
        $customerFixture->login();
        $cart = TddWizard\Checkout\CartBuilder::forCurrentSession()->withSimpleProduct('FreeProductTest-4', 2)->build();

        $order = TddWizard\Checkout\CustomerCheckout::fromCart($cart)
            ->withShippingMethodCode('flatrate_flatrate')
            ->withPaymentMethodCode('checkmo')
            ->withCustomerBillingAddressId($customerFixture->getDefaultShippingAddressId())
            ->withCustomerShippingAddressId($customerFixture->getDefaultShippingAddressId())
            ->placeOrder();

        $this->assertEquals(200, $order->getSubtotal());
        $this->assertTrue($this->testHelper->getFreeproductItem($order, 'freeproduct-5') !== null, 'Order does not contain Freeproduct item');
    }
}