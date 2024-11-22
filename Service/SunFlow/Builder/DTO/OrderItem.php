<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\OrderItem as orderItemDataProvider;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Shipment as ShipmentDataProvider;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItem;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItemExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItemPurchaseGoodExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandDetailItemPurchaseGoodExternalMultiSourceKey;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;

class OrderItem
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private orderItemDataProvider $orderItemDataProvider,
        private ShipmentDataProvider $shipmentDataProvider
    ) {
    }

    public function create(OrderInterface $order): array
    {
        $details = [];

        foreach ($order->getAllVisibleItems() as $orderItem) {
            if ($orderItem->getParentItem()) {
                continue;
            }
            $details[] = $this->buildOrderItemData($order, $orderItem);
        }

        if (!$order->getIsVirtual()) {
            $details[] = $this->buildOrderItemData(
                $order,
                $this->orderItemDataProvider->getShippingOrderItem($order),
                true
            );
        }

        return $details;
    }

    public function buildOrderItemData(
        OrderInterface $order,
        OrderItemInterface $orderItem,
        bool $isShipping = false
    ): SystemCRM360DataCommandDetailItem {
        [$trackingNumber, $trackingTitle] = $this->shipmentDataProvider->getTrackingInfo($order, $orderItem);

        return (new SystemCRM360DataCommandDetailItem())
            ->setExternalKey($this->buildDetailExternalKey($orderItem))
            ->setPurchaseGoodExternalMultiSourceKey($this->builderDetailExternalMultiSourceKey($orderItem, $isShipping))
            ->setPurchaseGoodExternalKey($this->buildDetailPurchaseGoodExternalKey($orderItem, $isShipping))
            ->setCarrierTrackerCode($isShipping ? null : $trackingNumber)
            ->setCarrierName($isShipping ? null : $trackingTitle)
            ->setPurchaseStatus($isShipping ? null : $this->orderItemDataProvider->getPurchaseStatus($orderItem))
            ->setPurchaseStatusDate($isShipping ? null :
                $this->orderItemDataProvider->getOrderItemUpdatedAt($orderItem))
            ->setAmount($this->orderItemDataProvider->getOrderItemTotalPrice($orderItem))
            ->setTaxFreeAmount($this->orderItemDataProvider->getOrderItemTotalPriceExclTax($orderItem))
            ->setDiscountAmount($this->orderItemDataProvider->getOrderItemTotalDiscount($orderItem))
            ->setQuantity($this->orderItemDataProvider->getOrderItemQty($orderItem));
    }

    public function buildDetailExternalKey(OrderItemInterface $orderItem): SystemCRM360DataCommandDetailItemExternalKey
    {
        return (new SystemCRM360DataCommandDetailItemExternalKey())
            ->setIdentifier((string) $this->orderItemDataProvider->getOrderItemProductId($orderItem))
            ->setSourceId($this->sunflowConfig->getSourceId());
    }

    public function buildDetailPurchaseGoodExternalKey(
        OrderItemInterface $orderItem,
        bool $isShipping = false
    ): SystemCRM360DataCommandDetailItemPurchaseGoodExternalKey {
        $itemSku = (string) $this->orderItemDataProvider->getOrderItemSku($orderItem);

        $purchaseGoodExternalKey = (new SystemCRM360DataCommandDetailItemPurchaseGoodExternalKey())
            ->setSku($itemSku)
            ->setIdentifier((string) $this->orderItemDataProvider->getOrderItemProductId($orderItem))
            ->setSourceId($this->sunflowConfig->getSourceId());

        if ($isShipping) {
            $purchaseGoodExternalKey = $purchaseGoodExternalKey
                ->setEan13($itemSku)
                ->setReference($itemSku);
        }

        return $purchaseGoodExternalKey;
    }

    public function builderDetailExternalMultiSourceKey(
        OrderItemInterface $orderItem,
        bool $isShipping = false
    ): SystemCRM360DataCommandDetailItemPurchaseGoodExternalMultiSourceKey {
        $itemSku = (string) $this->orderItemDataProvider->getOrderItemSku($orderItem);

        $purchaseGoodExternalMultiSourceKey =
            (new SystemCRM360DataCommandDetailItemPurchaseGoodExternalMultiSourceKey())
                ->setSku($itemSku)
                ->setIdentifier((string) $this->orderItemDataProvider->getOrderItemProductId($orderItem));

        if ($isShipping) {
            $purchaseGoodExternalMultiSourceKey = $purchaseGoodExternalMultiSourceKey
                ->setEan13($itemSku)
                ->setReference($itemSku);
        }

        return $purchaseGoodExternalMultiSourceKey;
    }
}
