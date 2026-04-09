<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Repositories\Contracts\ProductBatchRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBatchController extends Controller
{
    public function __construct(
        private readonly ProductBatchRepositoryInterface $batchRepository
    ) {}

    /** كل دفعات منتج معيّن (مرتبة حسب FEFO). */
    public function index(Product $product): JsonResponse
    {
        $batches = $product->batches()
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->get();

        return response()->json($batches);
    }

    /** إضافة دفعة جديدة ورفع المخزون. */
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'batch_code' => ['nullable', 'string', 'max:100'],
            'expiration_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'remaining_quantity' => ['nullable', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $qty = (int) $data['quantity'];
        $remaining = isset($data['remaining_quantity']) ? (int) $data['remaining_quantity'] : $qty;
        if ($remaining > $qty) {
            $remaining = $qty;
        }

        $batch = $this->batchRepository->create([
            'product_id' => $product->id,
            'batch_code' => $data['batch_code'] ?? null,
            'expiration_date' => $data['expiration_date'],
            'quantity' => $qty,
            'remaining_quantity' => $remaining,
            'cost_price' => $data['cost_price'] ?? null,
        ]);

        return response()->json($batch->fresh(), 201);
    }

    /** تعديل تفاصيل دفعة مع ضبط القيم المنطقية للكمية/المتبقي. */
    public function update(Request $request, ProductBatch $batch): JsonResponse
    {
        $data = $request->validate([
            'batch_code' => ['nullable', 'string', 'max:100'],
            'expiration_date' => ['sometimes', 'required', 'date'],
            'quantity' => ['sometimes', 'required', 'integer', 'min:1'],
            'remaining_quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        $nextQuantity = (int) ($data['quantity'] ?? $batch->quantity);
        $nextRemaining = (int) ($data['remaining_quantity'] ?? $batch->remaining_quantity);
        $nextRemaining = min($nextRemaining, $nextQuantity);

        $payload = [];
        if (array_key_exists('batch_code', $data)) {
            $payload['batch_code'] = $data['batch_code'];
        }
        if (array_key_exists('expiration_date', $data)) {
            $payload['expiration_date'] = $data['expiration_date'];
        }
        if (array_key_exists('quantity', $data)) {
            $payload['quantity'] = $nextQuantity;
        }
        if (array_key_exists('cost_price', $data)) {
            $payload['cost_price'] = $data['cost_price'];
        }
        if (array_key_exists('quantity', $data) || array_key_exists('remaining_quantity', $data)) {
            $payload['remaining_quantity'] = $nextRemaining;
        }

        $updated = $this->batchRepository->update($batch, $payload);

        return response()->json($updated);
    }
}
