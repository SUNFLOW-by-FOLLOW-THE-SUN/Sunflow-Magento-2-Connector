<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use DateTime;
use FollowTheSun\Connector\Service\DateFormat;
use FollowTheSun\SunflowSDK\Constant\Command\PurchaseTypeId;
use Magento\Backend\Model\Url;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;

class Order
{
    public function __construct(
        private StoreManagerInterface $storeManager,
        private Url $urlBuilder,
        private DateFormat $dateFormat,
    ) {
    }

    public function getEcommerceCommandId(OrderInterface $order): string
    {
        return $order->getIncrementId();
    }

    public function getStoreCode(OrderInterface $order): string
    {
        $orderStoreId = $order->getStoreId();

        try {
            return $this->storeManager->getStore($orderStoreId)->getCode();
        } catch (NoSuchEntityException $e) {
            return (string) $orderStoreId;
        }
    }

    public function getOrderCreationDate(OrderInterface $order, bool $withTimezone = false): ?DateTime
    {
        return $this->dateFormat->createDateTime((string) $order->getCreatedAt(), $withTimezone);
    }

    public function getCurrency(OrderInterface $order): string
    {
        return (string) $order->getOrderCurrencyCode();
    }

    public function getTotalPrice(OrderInterface $order): float
    {
        return (float) $order->getGrandTotal();
    }

    public function getTotalPriceExclTax(OrderInterface $order): float
    {
        return (float) abs(((float) $order->getGrandTotal()) - ((float) $order->getTaxAmount()));
    }

    public function getTotalDiscount(OrderInterface $order): float
    {
        return (float) abs((float) $order->getDiscountAmount());
    }

    public function getOrderUri(OrderInterface $order): string
    {
        return $this->urlBuilder->getUrl('sales/order/view', ['order_id' => $order->getId()]);
    }

    public function getOrderStatus(OrderInterface $order): string
    {
        try {
            return $order->getStatusLabel();
        } catch (LocalizedException $e) {
            return '';
        }
    }

    public function getOrderStatusUpdateDate(OrderInterface $order, bool $withTimezone = false): ?DateTime
    {
        return $this->dateFormat->createDateTime((string) $order->getUpdatedAt(), $withTimezone);
    }

    public function getCarrierName(OrderInterface $order): string
    {
        return $order->getShippingDescription();
    }

    public function getPaymentLabel(OrderInterface $order): string
    {
        try {
            return $order->getPayment()->getMethodInstance()->getTitle();
        } catch (LocalizedException $e) {
            return '';
        }
    }

    public function getPurchaseTypeId(OrderInterface $order): int
    {
        return (float) $order->getTotalDue() === 0.0 ? PurchaseTypeId::ORDER_PAID : PurchaseTypeId::ORDER_UNPAID;
    }
}
