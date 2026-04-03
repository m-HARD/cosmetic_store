<?php

namespace App\Repositories;

use App\Models\ProductBatch;
use App\Repositories\Contracts\ProductBatchRepositoryInterface;
use Illuminate\Support\Collection;

class ProductBatchRepository implements ProductBatchRepositoryInterface
{
    public function findById(int $id): ?ProductBatch
    {
        return ProductBatch::query()->find($id);
    }

    public function listAvailableByProductFefo(int $productId): Collection
    {
        return ProductBatch::query()
            ->where('product_id', $productId)
            ->available()
            ->fefo()
            ->get();
    }

    public function create(array $data): ProductBatch
    {
        return ProductBatch::query()->create($data);
    }

    public function update(ProductBatch $batch, array $data): ProductBatch
    {
        $batch->update($data);

        return $batch->refresh();
    }
}
