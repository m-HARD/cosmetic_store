<?php

namespace App\Repositories\Contracts;

use App\Models\Expense;
use Illuminate\Support\Collection;

interface ExpenseRepositoryInterface
{
    public function findById(int $id): ?Expense;

    public function latest(int $limit = 20): Collection;

    public function create(array $data): Expense;
}
