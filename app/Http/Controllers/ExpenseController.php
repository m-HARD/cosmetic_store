<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\ExpenseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly ExpenseRepositoryInterface $expenseRepository
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json($this->expenseRepository->latest(50));
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        return response()->json($this->expenseRepository->create($payload), 201);
    }
}
