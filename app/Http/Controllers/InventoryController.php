<?php

namespace App\Http\Controllers;

use App\Models\Loss;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryController extends Controller
{
    public function expiringSoon(Request $request): JsonResponse
    {
        $days = (int) $request->integer('days', 30);

        $items = ProductBatch::query()
            ->with('product:id,name')
            ->whereDate('expiration_date', '<=', now()->addDays($days))
            ->where('remaining_quantity', '>', 0)
            ->orderBy('expiration_date')
            ->get();

        return response()->json($items);
    }

    public function lowStock(): JsonResponse
    {
        $products = Product::query()
            ->withSum('batches as total_stock', 'remaining_quantity')
            ->whereRaw('(SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_batches WHERE product_batches.product_id = products.id) <= min_stock_alert')
            ->get();

        return response()->json($products);
    }

    /** جدول الدفعات مع المنتج (لشاشة المخزون). */
    public function batches(Request $request): JsonResponse
    {
        $query = ProductBatch::query()
            ->with(['product:id,name,barcode']);

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->integer('product_id'));
        }

        $rows = $query
            ->orderBy('expiration_date')
            ->orderBy('id')
            ->limit(500)
            ->get();

        return response()->json($rows);
    }

    public function markAsLoss(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:product_batches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'loss_value' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:100'],
        ]);

        $payload['created_by'] = (int) $request->user()->id;

        $loss = DB::transaction(function () use ($payload) {
            if (! empty($payload['batch_id'])) {
                $batch = ProductBatch::query()->lockForUpdate()->findOrFail($payload['batch_id']);
                if ($batch->product_id !== (int) $payload['product_id']) {
                    throw ValidationException::withMessages([
                        'batch_id' => ['الدفعة لا تنتمي لهذا المنتج.'],
                    ]);
                }
                if ($batch->remaining_quantity < $payload['quantity']) {
                    throw ValidationException::withMessages([
                        'quantity' => ['الكمية المتبقية في الدفعة أقل من المطلوب.'],
                    ]);
                }
                $batch->decrement('remaining_quantity', $payload['quantity']);
            }

            return Loss::query()->create($payload);
        });

        return response()->json($loss, 201);
    }
}
