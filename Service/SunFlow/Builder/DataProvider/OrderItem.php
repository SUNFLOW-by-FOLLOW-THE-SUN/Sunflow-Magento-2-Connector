<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use DateTime;
use FollowTheSun\Connector\Service\DateFormat;
use FollowTheSun\SunflowSDK\Constant\Command\ItemPurchaseStatus;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\OrderItemInterfaceFactory;

class OrderItem
{
    public function __construct(
        private OrderItemInterfaceFactory $orderItemFactory,
        private DateFormat $dateFormat
    ) {
    }

    public function getOrderItemSku(OrderItemInterface $orderItem): string
    {
        return $orderItem->getSku();
    }

    public function getOrderItemProductId(OrderItemInterface $orderItem): int
    {
        if (!$product = $orderItem->getProduct()) {
            return (int) $orderItem->getId();
        }

        return (int) $product->getId();
    }

    public function getOrderItemQty(OrderItemInterface $orderItem): float
    {
        return (float) $orderItem->getQtyOrdered();
    }

    public function getOrderItemTotalPrice(OrderItemInterface $orderItem): float
    {
        return (float) $orderItem->getRowTotalInclTax();
    }

    public function getOrderItemTotalPriceExclTax(OrderItemInterface $orderItem): float
    {
        return (float) $orderItem->getRowTotal();
    }

    public function getOrderItemTotalDiscount(OrderItemInterface $orderItem): float
    {
        return (float) $orderItem->getDiscountAmount();
    }

    public function getOrderItemUpdatedAt(OrderItemInterface $orderItem, bool $withTimezone = false): DateTime
    {
        return $this->dateFormat->createDateTime((string) $orderItem->getUpdatedAt(), $withTimezone);
    }

    public function getPurchaseStatus(OrderItemInterface $orderItem): string
    {
        $qtyShipped = (float) $orderItem->getQtyShipped();
        $qtyInvoiced = (float) $orderItem->getQtyInvoiced();
        $qtyOrdered = (float) $orderItem->getQtyOrdered();

        if ($qtyShipped > 0) {
            return $qtyShipped === $qtyOrdered ? ItemPurchaseStatus::SHIPPED : ItemPurchaseStatus::PARTIALLY_SHIPPED;
        }

        if ($qtyInvoiced > 0) {
            return $qtyInvoiced === $qtyOrdered ? ItemPurchaseStatus::INVOICED : ItemPurchaseStatus::PARTIALLY_INVOICED;
        }

        return ItemPurchaseStatus::ORDERED;
    }

    public function getShippingOrderItem(OrderInterface $order): OrderItemInterface
    {
        return $this->orderItemFactory->create()
            ->setSku('shipping-fee')
            ->setId('shipping-fee')
            ->setQtyOrdered(1)
            ->setRowTotalInclTax($order->getShippingInclTax())
            ->setRowTotal($order->getShippingAmount())
            ->setUpdatedAt($order->getUpdatedAt())
            ->setDiscountAmount($order->getShippingDiscountAmount());
    }
}
