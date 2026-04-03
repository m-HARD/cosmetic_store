<?php

namespace App\Http\Controllers;

use App\Services\POSService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class POSController extends Controller
{
    public function __construct(
        private readonly POSService $posService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'cashier_id' => ['required', 'integer', 'exists:users,id'],
            'payment_method' => ['required', 'string', 'in:cash,bankak'],
            'bankak_reference_last5' => ['nullable', 'string', 'size:5'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'tax' => ['nullable', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'change_amount' => ['nullable', 'numeric', 'min:0'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        // إنشاء البيع داخل transaction لضمان سلامة خصم المخزون مع الفاتورة.
        $sale = $this->posService->createSale($payload);

        return response()->json($sale, 201);
    }
}
