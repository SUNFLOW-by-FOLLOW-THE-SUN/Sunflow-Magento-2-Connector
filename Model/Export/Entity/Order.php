<?php

declare(strict_types=1);

namespace FollowTheSun\Connector\Model\Export\Entity;

use FollowTheSun\Connector\Service\Config\Export;
use FollowTheSun\Connector\Service\DateFormat;
use FollowTheSun\Connector\Service\Export\ExportDate;
use FollowTheSun\SunflowSDK\Constant\Command\ItemPurchaseStatus;
use Magento\Backend\Model\Url;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

class Order implements EntityInterface
{
    use EntityTrait;

    private const ENTITY_TYPE = 'Order';

    private ?array $orderStatusLabels = null;

    public function __construct(
        private Export $exportConfig,
        private DateFormat $dateFormatService,
        private ExportDate $exportDate,
        private ResourceConnection $resourceConnection,
        private Url $backendUrl
    ) {
    }

    public function buildLinesFromData(array $batch): array
    {
        $lines = [];

        $allOrderItems = $this->getOrderItemsData(array_keys($batch));
        foreach ($batch as $orderId => $orderData) {
            $orderItems = $allOrderItems[$orderId];
            foreach ($orderItems as $orderItem) {
                $lines[] = $this->buildLine($orderData, $orderItem);
            }

            unset($orderItems);

            if (!$orderData['so.is_virtual']) {
                $lines[] = $this->buildLine($orderData, [
                    'soi.item_id'            => uniqid(),
                    'soi.sku'                => 'shipping-fee',
                    'soi.product_id'         => 'shipping-fee',
                    'soi.qty_ordered'        => 1,
                    'soi.row_total_incl_tax' => $orderData['so.shipping_incl_tax'],
                    'soi.row_total'          => $orderData['so.shipping_amount'],
                    'soi.updated_at'         => $orderData['so.updated_at'],
                    'soi.discount_amount'    => $orderData['so.shipping_discount_amount'],
                    'soi.tax_amount'         => 0,
                    'sst.title'              => '',
                    'sst.track_number'       => '',
                ]);
            }
        }

        return $lines;
    }

    public function buildLine(array $batchData, array $orderItem): array
    {
        return [
            'ecommerce_command_id'             => $batchData['so.entity_id'],
            'order_reference'                  => $batchData['so.increment_id'],
            'code_magasin'                     => $batchData['store.code'] ?? 'default',
            'order_creation_date'              => $this->getOrderCreationDate($batchData['so.created_at']),
            'ecommerce_contact_id'             => $batchData['so.customer_id'],
            'currency'                         => $batchData['so.order_currency_code'],
            'total_price'                      => $batchData['so.grand_total'],
            'total_price_excl_tax'             => $this->getTotalPriceExclTax($batchData),
            'total_discount'                   => $this->getDiscountAmount($batchData),
            'order_uri'                        => $this->getOrderUi($batchData),
            'order_status'                     => $this->getStatusLabels()[$batchData['so.status']] ?? '',
            'order_status_update_date'         => $this->getOrderStatusUpdateDate($batchData['so.updated_at']),
            'payment_method'                   => $this->getPaymentMethod($batchData),
            'carrier_name'                     => $batchData['sst.title'],
            'carrier_tracking_code'            => $batchData['sst.track_number'],
            'carrier_tracking_uri'             => '',
            'line_id'                          => $orderItem['soi.item_id'],
            'product_sku'                      => $orderItem['soi.sku'],
            'product_ean13'                    => $orderItem['soi.sku'],
            'product_reference'                => $orderItem['soi.sku'],
            'product_purchase_good_identifier' => $orderItem['soi.product_id'],
            'product_status'                   => $this->getPurchaseStatus($orderItem),
            'product_status_date'              => $this->getOrderItemStatusDate($orderItem['soi.updated_at']),
            'product_quantity'                 => $orderItem['soi.qty_ordered'],
            'product_total_price'              => $orderItem['soi.row_total'] - $orderItem['soi.discount_amount'] + $orderItem['soi.tax_amount'],
            'product_total_price_excl_tax'     => $orderItem['soi.row_total'] - $orderItem['soi.discount_amount'],
            'product_total_discount'           => $orderItem['soi.discount_amount'],
            'product_carrier_name'             => $orderItem['sst.title'],
            'product_carrier_tracking_code'    => $orderItem['sst.track_number'],
            'product_carrier_tracking_uri'     => '',
        ];
    }

    public function getTotalPriceExclTax(array $batchData): float
    {
        return (float) abs(((float) $batchData['so.grand_total'] ?? 0) - ((float) $batchData['so.tax_amount'] ?? 0));
    }

    public function getDiscountAmount(array $batchData): float
    {
        return (float) abs((float) $batchData['so.discount_amount'] ?? 0);
    }

    public function getOrderUi(array $batchData): string
    {
        return $this->backendUrl->getUrl('sales/order/view', ['order_id' => $batchData['so.entity_id']]);
    }

    public function getPaymentMethod(array $batchData): string
    {
        return json_decode($batchData['sop.additional_information'] ?? '', true)['method_title'] ?? '';
    }

    public function getPurchaseStatus(array $batchData): string
    {
        $qtyShipped = (float) ($batchData['soi.qty_shipped'] ?? 0);
        $qtyInvoiced = (float) ($batchData['soi.qty_invoiced'] ?? 0);
        $qtyOrdered = (float) ($batchData['soi.qty_ordered'] ?? 0);

        if ($qtyShipped > 0) {
            return $qtyShipped === $qtyOrdered ? ItemPurchaseStatus::SHIPPED : ItemPurchaseStatus::PARTIALLY_SHIPPED;
        }

        if ($qtyInvoiced > 0) {
            return $qtyInvoiced === $qtyOrdered ? ItemPurchaseStatus::INVOICED : ItemPurchaseStatus::PARTIALLY_INVOICED;
        }

        return ItemPurchaseStatus::ORDERED;
    }

    public function getOrderCreationDate(string $orderCreatedAt): string
    {
        return $this->dateFormatService->getDateTimeFormatted(
            $this->dateFormatService->createDateTime($orderCreatedAt, true)
        );
    }

    public function getOrderStatusUpdateDate(string $orderUpdatedAt): string
    {
        return $this->dateFormatService->getDateTimeFormatted(
            $this->dateFormatService->createDateTime($orderUpdatedAt, true)
        );
    }

    public function getOrderItemStatusDate(string $orderItemUpdatedAt): string
    {
        return $this->dateFormatService->getDateTimeFormatted(
            $this->dateFormatService->createDateTime($orderItemUpdatedAt, true)
        );
    }

    public function getDataToExport(int $curPage = 0, int $batchSize = 1000): array
    {
        $connection = $this->resourceConnection->getConnection();
        $ordersData = $connection->fetchAll($this->getOrderSelect($curPage, $batchSize));

        $orders = [];
        foreach ($ordersData as $orderData) {
            $orders[$orderData['so.entity_id']] = $orderData;
        }

        return $orders;
    }

    public function getOrderSelect(int $curPage = 0, int $batchSize = 1000): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $salesOrderTable = $connection->getTableName('sales_order');
        $salesOrderPaymentTable = $connection->getTableName('sales_order_payment');
        $salesShipmentTable = $connection->getTableName('sales_shipment');
        $salesShipmentTrackTable = $connection->getTableName('sales_shipment_track');
        $storeTable = $connection->getTableName('store');

        $select = $connection->select()
            ->from(
                ['so' => $salesOrderTable],
                [
                    'so.entity_id'                => 'entity_id',
                    'so.increment_id'             => 'increment_id',
                    'so.created_at'               => 'created_at',
                    'so.updated_at'               => 'updated_at',
                    'so.customer_id'              => 'customer_id',
                    'so.order_currency_code'      => 'order_currency_code',
                    'so.grand_total'              => 'grand_total',
                    'so.tax_amount'               => 'tax_amount',
                    'so.discount_amount'          => 'discount_amount',
                    'so.status'                   => 'status',
                    'so.is_virtual'               => 'is_virtual',
                    'so.shipping_amount'          => 'shipping_amount',
                    'so.shipping_incl_tax'        => 'shipping_incl_tax',
                    'so.shipping_discount_amount' => 'shipping_discount_amount',
                ]
            )->joinLeft(
                ['sop' => $salesOrderPaymentTable],
                'sop.parent_id = so.entity_id',
                [
                    'sop.additional_information' => 'additional_information'
                ]
            )->joinLeft(
                ['ss' => $salesShipmentTable],
                'ss.order_id = so.entity_id',
                [
                    'ss.entity_id' => 'entity_id',
                ]
            )->joinLeft(
                ['sst' => $salesShipmentTrackTable],
                'sst.parent_id = ss.entity_id',
                [
                    'sst.track_number' => 'track_number',
                    'sst.title'        => 'title',
                ]
            )->joinLeft(
                ['store' => $storeTable],
                'so.store_id = store.store_id',
                [
                    'store.code' => 'code',
                ]
            )
            ->order('so.entity_id ASC')
            ->limit($batchSize, $batchSize * $curPage);

        if ($lastExportDate = $this->getLastExportDateForEntityType()) {
            $select->where('so.updated_at >= ?', $lastExportDate);
        }

        return $select;
    }

    public function getOrderItemsData(array $orderIds): array
    {
        $connection = $this->resourceConnection->getConnection();
        $orderItemsData = $connection->fetchAll($this->getOrderItemSelect($orderIds));

        $orderItems = [];
        foreach ($orderItemsData as $orderItemData) {
            $orderItems[$orderItemData['soi.order_id']][] = $orderItemData;
        }

        return $orderItems;
    }

    public function getOrderItemSelect(array $orderIds): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $salesOrderItemTable = $connection->getTableName('sales_order_item');
        $salesShipmentItemTable = $connection->getTableName('sales_shipment_item');
        $salesShipmentTrackTable = $connection->getTableName('sales_shipment_track');

        return $connection->select()->from(
            ['soi' => $salesOrderItemTable],
            [
                'soi.item_id'            => 'item_id',
                'soi.order_id'           => 'order_id',
                'soi.sku'                => 'sku',
                'soi.product_id'         => 'product_id',
                'soi.qty_invoiced'       => 'qty_invoiced',
                'soi.qty_shipped'        => 'qty_shipped',
                'soi.qty_ordered'        => 'qty_ordered',
                'soi.updated_at'         => 'updated_at',
                'soi.row_total_incl_tax' => 'row_total_incl_tax',
                'soi.row_total'          => 'row_total',
                'soi.discount_amount'    => 'discount_amount',
                'soi.tax_amount'         => 'tax_amount',
            ]
        )->joinLeft(
            ['ssi' => $salesShipmentItemTable],
            'ssi.order_item_id = soi.item_id',
            [
                'ssi.parent_id' => 'parent_id',
            ]
        )->joinLeft(
            ['sst' => $salesShipmentTrackTable],
            'sst.parent_id = ssi.parent_id',
            [
                'sst.track_number' => 'track_number',
                'sst.title'        => 'title',
            ]
        )->where('soi.order_id IN (?)', $orderIds);
    }

    public function getStatusLabels(): array
    {
        if (!$this->orderStatusLabels) {
            $connection = $this->resourceConnection->getConnection();
            $status = $connection->fetchAll(
                $connection->select()->from($connection->getTableName('sales_order_status'))
            );

            foreach ($status as $row) {
                $this->orderStatusLabels[$row['status']] = $row['label'];
            }
        }

        return $this->orderStatusLabels;
    }

    public function getLastExportDateForEntityType(): ?string
    {
        if ($this->deltaOnly()) {
            return $this->exportDate->getLastExportDateForEntityType($this->getEntityType());
        }

        return null;
    }

    public function deltaOnly(): bool
    {
        return $this->exportConfig->isDeltaModeEnabled();
    }
}
