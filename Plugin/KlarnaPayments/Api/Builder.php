<?php

namespace C4B\FreeProduct\Plugin\KlarnaPayments\Api;

use C4B\FreeProduct\SalesRule\Action\GiftAction;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Model\Quote;

/**
 * Klarna can recollect totals, if that happens the quote is not saved and the freeproduct items will not have an ID yet.
 *
 * @package    C4B_FreeProduct
 * @author     Dominik Meglič <meglic@code4business.de>
 * @copyright  code4business Software GmbH
 * @license    http://opensource.org/licenses/osl-3.0.php
 */
class Builder
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @param CartRepositoryInterface $cartRepository
     */
    public function __construct(CartRepositoryInterface $cartRepository)
    {
        $this->cartRepository = $cartRepository;
    }

    /**
     * @param \Klarna\Core\Api\BuilderInterface $subject
     * @param \Klarna\Core\Api\BuilderInterface $resultThis
     * @param string $type
     * @return \Klarna\Core\Api\BuilderInterface
     * @see    \Klarna\Core\Api\BuilderInterface::generateRequest
     * @plugin after
     */
    public function afterGenerateRequest(\Klarna\Core\Api\BuilderInterface $subject, $resultThis, $type)
    {
        $salesObject = $subject->getObject();

        if ($type == \Klarna\Core\Api\BuilderInterface::GENERATE_TYPE_CREATE &&
            $salesObject instanceof Quote)
        {
            foreach ($salesObject->getAllVisibleItems() as $quoteItem)
            {
                if ($quoteItem->getId() == null &&
                    $quoteItem->isDeleted() == false &&
                    $quoteItem->getOptionByCode(GiftAction::ITEM_OPTION_UNIQUE_ID) instanceof Quote\Item\Option)
                {
                    $this->cartRepository->save($salesObject);
                    break;
                }
            }
        }

        return $resultThis;
    }
}