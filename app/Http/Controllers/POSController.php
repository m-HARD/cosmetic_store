<?php

namespace App\Http\Controllers;

use App\Services\POSService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class POSController extends Controller
{
    public function __construct(
        private readonly POSService $posService
    ) {}

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'payment_method' => ['required', 'string', 'in:cash,bankak'],
            'bankak_reference_last5' => [
                Rule::requiredIf($request->input('payment_method') === 'bankak'),
                'nullable',
                'string',
                'size:5',
            ],
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

        $payload['cashier_id'] = (int) $request->user()->id;
        if ($payload['payment_method'] === 'cash') {
            $payload['bankak_reference_last5'] = null;
        }

        try {
            $sale = $this->posService->createSale($payload);
        } catch (RuntimeException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json($sale, 201);
    }
}
