<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use DateTime;
use FollowTheSun\Connector\Service\DateFormat;
use FollowTheSun\SunflowSDK\Constant\Command\PurchaseTypeId;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\CartItemInterfaceFactory;
use Magento\Store\Model\StoreManagerInterface;

class Quote
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private CartItemInterfaceFactory $cartItemInterfaceFactory,
        private DateFormat $dateFormat
    ) {
    }

    public function getEcommerceCommandId(CartInterface $cart): string
    {
        return $cart->getReservedOrderId() ?? (string) $cart->getId();
    }

    public function getStoreCode(CartInterface $cart): string
    {
        $cartStoreId = $cart->getStoreId();

        try {
            return $this->storeManager->getStore($cartStoreId)->getCode();
        } catch (NoSuchEntityException $e) {
            return (string) $cartStoreId;
        }
    }

    public function getQuoteCreationDate(CartInterface $cart, bool $withTimeZone = false): ?DateTime
    {
        $now = $this->dateFormat->createNowDateTime($withTimeZone);
        if ($createdAt = $cart->getCreatedAt()) {
            return $this->dateFormat->createDateTime($createdAt, $withTimeZone) ?: $now;
        }

        return $now;
    }

    public function getCurrency(CartInterface $cart): string
    {
        return (string) $cart->getStoreCurrencyCode();
    }

    public function getTotalPrice(CartInterface $cart): float
    {
        return (float) $cart->getGrandTotal();
    }

    public function getTotalPriceExclTax(CartInterface $cart): float
    {
        return (float) $cart->getSubtotal();
    }

    public function getTotalDiscount(CartInterface $cart): float
    {
        return (float) abs((float) $cart->getSubtotalWithDiscount() - (float) $cart->getSubtotal());
    }

    public function getQuoteStatusUpdateDate(CartInterface $cart, bool $withTimezone = false): ?DateTime
    {
        if (!$cart->getUpdatedAt()) {
            return null;
        }

        return $this->dateFormat->createDateTime((string) $cart->getUpdatedAt(), $withTimezone);
    }

    public function getCartItemSku(CartItemInterface $cartItem): string
    {
        return $cartItem->getSku();
    }

    public function getCartItemProductId(CartItemInterface $cartItem): int
    {
        $cartItemId = (int) $cartItem->getId();
        if ($cartItemId === 0 || !$product = $cartItem->getProduct()) {
            return $cartItemId;
        }

        return (int) $product->getId();
    }

    public function getCartItemQty(CartItemInterface $cartItem): float
    {
        return (float) $cartItem->getQty();
    }

    public function getCartItemTotalPrice(CartItemInterface $cartItem): float
    {
        return (float) $cartItem->getRowTotalInclTax();
    }

    public function getCartItemTotalPriceExclTax(CartItemInterface $cartItem): float
    {
        return (float) $cartItem->getRowTotal();
    }

    public function getCartItemTotalDiscount(CartItemInterface $cartItem): float
    {
        return (float) $cartItem->getDiscountAmount();
    }

    public function getPurchaseTypeId(): int
    {
        return PurchaseTypeId::ORDER_CART;
    }

    public function getShippingCartItem(CartInterface $cart): ?CartItemInterface
    {
        if (!$cartShipping = $cart->getShippingAddress()) {
            return null;
        }

        return $this->cartItemInterfaceFactory->create()
            ->setRowTotalInclTax($cartShipping->getShippingInclTax())
            ->setRowTotal($cartShipping->getShippingAmount())
            ->setDiscountAmount($cartShipping->getShippingDiscountAmount())
            ->setSku('shipping-fee')
            ->setId('shipping-fee')
            ->setQty(1)
            ->setQuoteId($cart->getId());
    }
}
