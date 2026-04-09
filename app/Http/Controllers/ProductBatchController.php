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

    /** تعديل الكمية المتبقية فقط (تصحيح جرد) دون تجاوز الكمية الأصلية. */
    public function update(Request $request, ProductBatch $batch): JsonResponse
    {
        $data = $request->validate([
            'remaining_quantity' => ['required', 'integer', 'min:0'],
        ]);

        $max = (int) $batch->quantity;
        $newRem = min((int) $data['remaining_quantity'], $max);

        $updated = $this->batchRepository->update($batch, [
            'remaining_quantity' => $newRem,
        ]);

        return response()->json($updated);
    }
}
