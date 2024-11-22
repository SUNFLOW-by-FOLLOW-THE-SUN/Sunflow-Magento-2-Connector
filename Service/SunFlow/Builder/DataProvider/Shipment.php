<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Service\SunFlow\Builder\DataProvider;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\ShipmentRepositoryInterface;

class Shipment
{
    public function __construct(
        private ShipmentRepositoryInterface $shipmentRepository,
    ) {
    }

    public function getTrackingInfo(OrderInterface $order, ?OrderItemInterface $orderItem = null): array
    {
        foreach ($order->getTracksCollection()->getItems() as $track) {
            $shipment = $this->shipmentRepository->get($track->getParentId());
            $items = $shipment->getItems();
            foreach ($items as $item) {
                if (!$orderItem) {
                    return [$track->getTrackNumber(), $track->getTitle()];
                }

                if ($track->getTrackNumber() && $item->getSku() === $orderItem->getSku()) {
                    return [$track->getTrackNumber(), $track->getTitle()];
                }
            }
        }

        return ['', ''];
    }
}
