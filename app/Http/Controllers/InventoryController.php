<?php

namespace App\Http\Controllers;

use App\Models\Loss;
use App\Models\Product;
use App\Models\ProductBatch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

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
            ->whereRaw('(SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_batches WHERE product_batches.product_id = products.id) <= min_stock_alert')
            ->get();

        return response()->json($products);
    }

    public function markAsLoss(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'batch_id' => ['nullable', 'integer', 'exists:product_batches,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'loss_value' => ['required', 'numeric', 'min:0'],
            'reason' => ['required', 'string', 'max:100'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        // تسجيل الخسارة في جدول مستقل لتسهيل التحليل المالي والتدقيق.
        $loss = Loss::query()->create($payload);

        return response()->json($loss, 201);
    }
}
