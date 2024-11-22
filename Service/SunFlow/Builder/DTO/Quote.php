<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Quote as QuoteDataProvider;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommand;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandContactExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItem;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItemPurchaseGoodExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItemPurchaseGoodExternalMultiSourceKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandStoreExternalKey;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;

class Quote
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private QuoteDataProvider $quoteDataProvider,
    ) {
    }

    public function create(CartInterface $cart): SystemCRM360DataCommand
    {
        return (new SystemCRM360DataCommand())
            ->setExternalKey($this->buildExternalKey($cart))
            ->setContactExternalKey($this->buildContactExternalKey($cart))
            ->setPurchaseDate($this->quoteDataProvider->getQuoteCreationDate($cart, true))
            ->setPurchaseStatusDate($this->quoteDataProvider->getQuoteStatusUpdateDate($cart))
            ->setStoreExternalKey($this->buildStoreExternalKey($cart))
            ->setCurrencyId($this->quoteDataProvider->getCurrency($cart))
            ->setAmount($this->quoteDataProvider->getTotalPrice($cart))
            ->setTaxFreeAmount($this->quoteDataProvider->getTotalPriceExclTax($cart))
            ->setDiscountAmount($this->quoteDataProvider->getTotalDiscount($cart))
            ->setPurchaseTypeId($this->quoteDataProvider->getPurchaseTypeId())
            ->setBrandId($this->sunflowConfig->getBrandId())
            ->setDetails($this->buildDetails($cart));
    }

    public function buildExternalKey(CartInterface $cart): SystemCRM360DataCommandExternalKey
    {
        return (new SystemCRM360DataCommandExternalKey())
            ->setPurchaseIdentifier($this->quoteDataProvider->getEcommerceCommandId($cart))
            ->setCheckoutIdentifier('-')
            ->setStoreIdentifier($this->quoteDataProvider->getStoreCode($cart))
            ->setPurchaseDate($this->quoteDataProvider->getQuoteCreationDate($cart, true))
            ->setSourceId($this->sunflowConfig->getSourceId());
    }

    public function buildStoreExternalKey(CartInterface $cart): SystemCRM360DataCommandStoreExternalKey
    {
        return (new SystemCRM360DataCommandStoreExternalKey())
            ->setStoreIdentifier($this->quoteDataProvider->getStoreCode($cart))
            ->setSourceId(0);
    }

    public function buildContactExternalKey(CartInterface $cart): SystemCRM360DataCommandContactExternalKey
    {
        return (new SystemCRM360DataCommandContactExternalKey())
            ->setIdentifier((string) $cart->getCustomerId())
            ->setSourceId($this->sunflowConfig->getSourceId());
    }

    public function buildDetails(CartInterface $cart): array
    {
        $details = [];

        foreach ($cart->getAllVisibleItems() as $cartItem) {
            $details[] = $this->buildCartItemData($cartItem);
        }

        if (!$cart->getIsVirtual() && $shippingItem = $this->quoteDataProvider->getShippingCartItem($cart)) {
            $details[] = $this->buildCartItemData($shippingItem);
        }

        return $details;
    }

    public function buildCartItemData(CartItemInterface $cartItem): SystemCRM360DataCommandDetailItem
    {
        return (new SystemCRM360DataCommandDetailItem())
            ->setPurchaseGoodExternalMultiSourceKey(
                (new SystemCRM360DataCommandDetailItemPurchaseGoodExternalMultiSourceKey())->setSku(
                    $this->quoteDataProvider->getCartItemSku($cartItem)
                )->setIdentifier(
                    (string) $this->quoteDataProvider->getCartItemProductId($cartItem)
                )
            )
            ->setPurchaseGoodExternalKey(
                (new SystemCRM360DataCommandDetailItemPurchaseGoodExternalKey())->setSku(
                    (string) $this->quoteDataProvider->getCartItemSku($cartItem)
                )->setIdentifier(
                    (string) $this->quoteDataProvider->getCartItemProductId($cartItem)
                )->setSourceId($this->sunflowConfig->getSourceId())
            )
            ->setAmount($this->quoteDataProvider->getCartItemTotalPrice($cartItem))
            ->setTaxFreeAmount($this->quoteDataProvider->getCartItemTotalPriceExclTax($cartItem))
            ->setDiscountAmount($this->quoteDataProvider->getCartItemTotalDiscount($cartItem))
            ->setQuantity($this->quoteDataProvider->getCartItemQty($cartItem));
    }
}
