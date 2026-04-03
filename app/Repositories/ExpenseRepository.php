<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Repositories\Contracts\ExpenseRepositoryInterface;
use Illuminate\Support\Collection;

class ExpenseRepository implements ExpenseRepositoryInterface
{
    public function findById(int $id): ?Expense
    {
        return Expense::query()->find($id);
    }

    public function latest(int $limit = 20): Collection
    {
        return Expense::query()
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Expense
    {
        return Expense::query()->create($data);
    }
}
