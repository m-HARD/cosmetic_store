<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductBatchRepositoryInterface;
use Illuminate\Support\Collection;
use RuntimeException;

class StockService
{
    public function __construct(
        private readonly ProductBatchRepositoryInterface $batchRepository
    ) {
    }

    public function allocateBatchesFefo(Product $product, int $quantity): Collection
    {
        $remaining = $quantity;
        $allocations = collect();
        $batches = $this->batchRepository->listAvailableByProductFefo($product->id);

        foreach ($batches as $batch) {
            if ($remaining <= 0) {
                break;
            }

            $fromBatch = min($remaining, $batch->remaining_quantity);
            $batch->decrement('remaining_quantity', $fromBatch);

            $allocations->push([
                'batch_id' => $batch->id,
                'quantity' => $fromBatch,
            ]);

            $remaining -= $fromBatch;
        }

        if ($remaining > 0) {
            throw new RuntimeException("Insufficient stock for product #{$product->id}");
        }

        return $allocations;
    }

    public function returnToBatch(int $batchId, int $quantity): void
    {
        $batch = $this->batchRepository->findById($batchId);

        if (! $batch) {
            throw new RuntimeException("Batch #{$batchId} not found.");
        }

        $batch->increment('remaining_quantity', $quantity);
    }
}
