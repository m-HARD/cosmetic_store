<?php

namespace App\Repositories;

use App\Models\Sale;
use App\Repositories\Contracts\SaleRepositoryInterface;
use Illuminate\Support\Collection;

class SaleRepository implements SaleRepositoryInterface
{
    public function findById(int $id): ?Sale
    {
        return Sale::query()->with('items')->find($id);
    }

    public function findByInvoiceNo(string $invoiceNo): ?Sale
    {
        return Sale::query()
            ->with('items')
            ->where('invoice_no', $invoiceNo)
            ->first();
    }

    public function latest(int $limit = 20): Collection
    {
        return Sale::query()
            ->latest('sold_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): Sale
    {
        return Sale::query()->create($data);
    }
}
