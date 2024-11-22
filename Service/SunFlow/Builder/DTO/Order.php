<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Order as OrderDataProvider;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Shipment as ShipmentDataProvider;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommand;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandContactExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandStoreExternalKey;
use Magento\Sales\Api\Data\OrderInterface;

class Order
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private OrderDataProvider $orderDataProvider,
        private ShipmentDataProvider $shipmentDataProvider,
        private OrderItem $orderItemBuilder
    ) {
    }

    public function create(OrderInterface $order): SystemCRM360DataCommand
    {
        [$trackingNumber, $trackingTitle] = $this->shipmentDataProvider->getTrackingInfo($order);

        return (new SystemCRM360DataCommand())
            ->setExternalKey($this->buildExternalKey($order))
            ->setContactExternalKey($this->buildContactExternalKey($order))
            ->setPurchaseDate($this->orderDataProvider->getOrderCreationDate($order, true))
            ->setPurchaseStatusDate($this->orderDataProvider->getOrderStatusUpdateDate($order, true))
            ->setPurchaseStatus($this->orderDataProvider->getOrderStatus($order))
            ->setPaymentMethod($this->orderDataProvider->getPaymentLabel($order))
            ->setStoreExternalKey($this->buildStoreExternalKey($order))
            ->setBackofficeUri($this->orderDataProvider->getOrderUri($order))
            ->setCarrierUri('')
            ->setCarrierName($trackingTitle)
            ->setCarrierTrackerCode($trackingNumber)
            ->setCurrencyId($this->orderDataProvider->getCurrency($order))
            ->setAmount($this->orderDataProvider->getTotalPrice($order))
            ->setTaxFreeAmount($this->orderDataProvider->getTotalPriceExclTax($order))
            ->setDiscountAmount($this->orderDataProvider->getTotalDiscount($order))
            ->setPurchaseTypeId($this->orderDataProvider->getPurchaseTypeId($order))
            ->setBrandId($this->sunflowConfig->getBrandId())
            ->setDetails($this->orderItemBuilder->create($order));
    }

    public function buildExternalKey(OrderInterface $order): SystemCRM360DataCommandExternalKey
    {
        return (new SystemCRM360DataCommandExternalKey())
            ->setPurchaseIdentifier($this->orderDataProvider->getEcommerceCommandId($order))
            ->setCheckoutIdentifier('-')
            ->setStoreIdentifier($this->orderDataProvider->getStoreCode($order))
            ->setPurchaseDate($this->orderDataProvider->getOrderCreationDate($order, true))
            ->setSourceId($this->sunflowConfig->getSourceId());
    }

    public function buildStoreExternalKey(OrderInterface $order): SystemCRM360DataCommandStoreExternalKey
    {
        return (new SystemCRM360DataCommandStoreExternalKey())
            ->setStoreIdentifier($this->orderDataProvider->getStoreCode($order))
            ->setSourceId(0);
    }

    public function buildContactExternalKey(OrderInterface $order): SystemCRM360DataCommandContactExternalKey
    {
        return (new SystemCRM360DataCommandContactExternalKey())
            ->setIdentifier((string) $order->getCustomerId())
            ->setSourceId($this->sunflowConfig->getSourceId());
    }
}
