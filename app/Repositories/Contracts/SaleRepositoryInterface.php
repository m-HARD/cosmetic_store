<?php

namespace App\Repositories\Contracts;

use App\Models\Sale;
use Illuminate\Support\Collection;

interface SaleRepositoryInterface
{
    public function findById(int $id): ?Sale;

    public function findByInvoiceNo(string $invoiceNo): ?Sale;

    public function latest(int $limit = 20): Collection;

    public function create(array $data): Sale;
}
