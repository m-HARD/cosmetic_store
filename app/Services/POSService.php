<?php

namespace App\Services;

use App\Models\Sale;
use App\Models\SaleItem;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class POSService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SaleRepositoryInterface $saleRepository,
        private readonly StockService $stockService,
        private readonly InvoiceNumberService $invoiceNumberService
    ) {
    }

    public function createSale(array $payload): Sale
    {
        return DB::transaction(function () use ($payload): Sale {
            $sale = $this->saleRepository->create([
                'invoice_no' => $this->invoiceNumberService->generate(),
                'sold_at' => now(),
                'cashier_id' => $payload['cashier_id'],
                'payment_method' => $payload['payment_method'],
                'bankak_reference_last5' => $payload['bankak_reference_last5'] ?? null,
                'subtotal' => $payload['subtotal'],
                'discount' => $payload['discount'] ?? 0,
                'tax' => $payload['tax'] ?? 0,
                'total' => $payload['total'],
                'paid_amount' => $payload['paid_amount'] ?? 0,
                'change_amount' => $payload['change_amount'] ?? 0,
                'status' => 'completed',
            ]);

            foreach ($payload['items'] as $item) {
                $product = $this->productRepository->findById((int) $item['product_id']);

                if (! $product) {
                    throw new RuntimeException('Product not found while creating sale.');
                }

                $allocations = $this->stockService->allocateBatchesFefo($product, (int) $item['quantity']);

                foreach ($allocations as $allocation) {
                    SaleItem::query()->create([
                        'sale_id' => $sale->id,
                        'product_id' => $product->id,
                        'batch_id' => $allocation['batch_id'],
                        'quantity' => $allocation['quantity'],
                        'unit_price' => $item['unit_price'],
                        'line_total' => $allocation['quantity'] * $item['unit_price'],
                    ]);
                }
            }

            return $sale->load('items');
        });
    }
}
