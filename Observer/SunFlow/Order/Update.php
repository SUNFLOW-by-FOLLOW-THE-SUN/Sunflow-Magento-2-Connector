<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Observer\SunFlow\Order;

use FollowTheSun\Connector\Service\Debug;
use FollowTheSun\Connector\Service\SunFlow\Events\Order\Update as OrderUpdateSunFlowService;
use FollowTheSun\Connector\Service\SunFlow\Events\Order\UpdateStatus as OrderUpdateStatusSunFlowService;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order\StatusLabel;

class Update implements ObserverInterface
{
    public function __construct(
        private OrderUpdateSunFlowService $orderUpdateSunFlowService,
        private OrderUpdateStatusSunFlowService $orderUpdateStatusSunFlowService,
        private Debug $debug,
        private StatusLabel $statusLabel
    ) {
    }

    public function execute(Observer $observer): void
    {
        /** @var OrderInterface $order */
        if (!$order = $observer->getEvent()->getOrder()) {
            $this->debug->debug('[Order Update Observer] No order found.');

            return;
        }

        $this->updateOrder($order);
        $this->updateStatus($order);
    }

    public function updateOrder(OrderInterface $order): void
    {
        $this->orderUpdateSunFlowService->send($order);
    }

    public function updateStatus(OrderInterface $order): void
    {
        $originStatus = $order->getOrigData()['status'] ?? null;
        $status = $order->getStatus();
        if ($originStatus && $status && $status !== $originStatus) {
            $this->orderUpdateStatusSunFlowService->send($order, $this->statusLabel->getStatusLabel($status));
        }
    }
}
