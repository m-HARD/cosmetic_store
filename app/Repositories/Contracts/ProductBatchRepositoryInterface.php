<?php

namespace App\Repositories\Contracts;

use App\Models\ProductBatch;
use Illuminate\Support\Collection;

interface ProductBatchRepositoryInterface
{
    public function findById(int $id): ?ProductBatch;

    public function listAvailableByProductFefo(int $productId): Collection;

    public function create(array $data): ProductBatch;

    public function update(ProductBatch $batch, array $data): ProductBatch;
}
