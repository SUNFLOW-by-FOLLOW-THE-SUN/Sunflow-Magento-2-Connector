<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DTO;

use FollowTheSun\Connector\Service\Config\SunFlow;
use FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider\Order as OrderDataProvider;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataCommandExternalKey;
use FollowTheSun\SunflowSDK\Command\Model\SystemCRM360DataStatusCommand;
use Magento\Sales\Api\Data\OrderInterface;

class StatusCommand
{
    public function __construct(
        private SunFlow $sunflowConfig,
        private OrderDataProvider $orderDataProvider,
    ) {
    }

    public function create(OrderInterface $order, ?string $newStatusLabel = null): SystemCRM360DataStatusCommand
    {
        return (new SystemCRM360DataStatusCommand())
            ->setExternalKey($this->buildExternalKey($order))
            ->setPurchaseStatusDate($this->orderDataProvider->getOrderStatusUpdateDate($order, true))
            ->setPurchaseStatus($newStatusLabel ?? $this->orderDataProvider->getOrderStatus($order))
            ->setPaymentMethod($this->orderDataProvider->getPaymentLabel($order))
            ->setBackofficeUri($this->orderDataProvider->getOrderUri($order))
            ->setCarrierUri('')
            ->setCarrierName($this->orderDataProvider->getCarrierName($order))
            ->setCarrierTrackerCode($this->orderDataProvider->getTrackingCode($order));
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
}
