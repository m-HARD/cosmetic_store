<?php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ProductRepository implements ProductRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with(['category', 'supplier'])
            ->withSum('batches as total_stock', 'remaining_quantity')
            ->latest('id')
            ->paginate($perPage);
    }

    public function allActive(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function activeForPosCatalog(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->withSum([
                'batches as available_stock' => static function ($query) {
                    $query->where('remaining_quantity', '>', 0);
                },
            ], 'remaining_quantity')
            ->orderBy('name')
            ->get()
            ->map(static function (Product $product) {
                $stock = (int) ($product->available_stock ?? 0);

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sale_price' => (float) $product->sale_price,
                    'category_id' => $product->category_id,
                    'barcode' => $product->barcode,
                    'image' => $product->image
                        ? Storage::disk('public')->url($product->image)
                        : null,
                    'stock' => $stock,
                ];
            });
    }

    public function findByBarcode(string $barcode): ?Product
    {
        return Product::query()
            ->where('barcode', $barcode)
            ->first();
    }

    public function findById(int $id): ?Product
    {
        return Product::query()->find($id);
    }

    public function create(array $data): Product
    {
        return Product::query()->create($data);
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh();
    }
}
