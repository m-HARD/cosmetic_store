<?php

namespace App\Http\Controllers;

use App\Services\RefundService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RefundController extends Controller
{
    public function __construct(
        private readonly RefundService $refundService
    ) {
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'invoice_no' => ['required', 'string'],
            'processed_by' => ['required', 'integer', 'exists:users,id'],
            'total_amount' => ['required', 'numeric', 'min:0'],
            'reason' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.sale_item_id' => ['required', 'integer'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        return response()->json($this->refundService->processRefund($payload), 201);
    }
}
