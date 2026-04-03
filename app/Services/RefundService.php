<?php

namespace App\Services;

use App\Models\RefundItem;
use App\Repositories\Contracts\RefundRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RefundService
{
    public function __construct(
        private readonly RefundRepositoryInterface $refundRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly StockService $stockService
    ) {
    }

    public function processRefund(array $payload)
    {
        return DB::transaction(function () use ($payload) {
            $sale = $this->saleRepository->findByInvoiceNo($payload['invoice_no']);

            if (! $sale) {
                throw new RuntimeException('Sale not found for refund.');
            }

            $refund = $this->refundRepository->create([
                'refund_no' => 'REF-'.now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
                'sale_id' => $sale->id,
                'processed_by' => $payload['processed_by'],
                'total_amount' => $payload['total_amount'],
                'reason' => $payload['reason'] ?? null,
                'refunded_at' => now(),
            ]);

            foreach ($payload['items'] as $item) {
                $saleItem = $sale->items->firstWhere('id', $item['sale_item_id']);

                if (! $saleItem) {
                    throw new RuntimeException('Invalid sale item in refund payload.');
                }

                RefundItem::query()->create([
                    'refund_id' => $refund->id,
                    'sale_item_id' => $saleItem->id,
                    'product_id' => $saleItem->product_id,
                    'batch_id' => $saleItem->batch_id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $saleItem->unit_price,
                    'line_total' => $item['quantity'] * $saleItem->unit_price,
                ]);

                if ($saleItem->batch_id) {
                    $this->stockService->returnToBatch((int) $saleItem->batch_id, (int) $item['quantity']);
                }
            }

            return $refund->load('items');
        });
    }
}
