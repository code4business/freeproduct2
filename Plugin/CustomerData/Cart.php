<?php

namespace C4B\FreeProduct\Plugin\CustomerData;

class Cart extends \Magento\Checkout\CustomerData\Cart
{

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Checkout\Model\Cart
     */
    protected $checkoutCart;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Url
     */
    protected $catalogUrl;

    /**
     * @var \Magento\Quote\Model\Quote|null
     */
    protected $quote = null;

    /**
     * @var \Magento\Checkout\Helper\Data
     */
    protected $checkoutHelper;

    /**
     * @var ItemPoolInterface
     */
    protected $itemPoolInterface;

    /**
     * @var int|float
     */
    protected $summeryCount;

    /**
     * @var \Magento\Framework\View\LayoutInterface
     */
    protected $layout;

    /**
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Catalog\Model\ResourceModel\Url $catalogUrl
     * @param \Magento\Checkout\Model\Cart $checkoutCart
     * @param \Magento\Checkout\Helper\Data $checkoutHelper
     * @param ItemPoolInterface $itemPoolInterface
     * @param \Magento\Framework\View\LayoutInterface $layout
     * @param array $data
     * @codeCoverageIgnore
     */
    public function __construct(
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Catalog\Model\ResourceModel\Url $catalogUrl,
        \Magento\Checkout\Model\Cart $checkoutCart,
        \Magento\Checkout\Helper\Data $checkoutHelper,
        \Magento\Checkout\CustomerData\ItemPoolInterface $itemPoolInterface,
        \Magento\Framework\View\LayoutInterface $layout,
        array $data = []
    ) {
        parent::__construct($checkoutSession, $catalogUrl, $checkoutCart, $checkoutHelper, $itemPoolInterface, $layout, $data);

        $this->itemPoolInterface = $itemPoolInterface;
        $this->catalogUrl = $catalogUrl;
    }

    /**
     * Get array of last added items
     * Add support to print on minicart product of type freeproduct_gift if are setted on Not Visible Individually
     *
     * @return \Magento\Quote\Model\Quote\Item[]
     */
    protected function getRecentItems()
    {
        $items = [];
        if (!$this->getSummaryCount()) {
            return $items;
        }

        foreach (array_reverse($this->getAllQuoteItems()) as $item) {
            /* @var $item \Magento\Quote\Model\Quote\Item */
            if (!$item->getProduct()->isVisibleInSiteVisibility()) {
                $product =  $item->getOptionByCode('product_type') !== null
                    ? $item->getOptionByCode('product_type')->getProduct()
                    : $item->getProduct();

                $products = $this->catalogUrl->getRewriteByProductStore([$product->getId() => $item->getStoreId()]);
                if (!isset($products[$product->getId()]) && ($product->getCustomOption('product_type')->getValue() != \C4B\FreeProduct\SalesRule\Action\GiftAction::PRODUCT_TYPE_FREEPRODUCT)) {
                    continue;
                }
                if($product->getCustomOption('product_type')->getValue() != \C4B\FreeProduct\SalesRule\Action\GiftAction::PRODUCT_TYPE_FREEPRODUCT) {
                    $urlDataObject = new \Magento\Framework\DataObject($products[$product->getId()]);
                    $item->getProduct()->setUrlDataObject($urlDataObject);
                }
            }
            $items[] = $this->itemPoolInterface->getItemData($item);
        }
        return $items;
    }

}
