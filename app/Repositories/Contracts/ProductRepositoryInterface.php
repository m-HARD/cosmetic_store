<?php

namespace App\Repositories\Contracts;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface ProductRepositoryInterface
{
    public function paginate(int $perPage = 15): LengthAwarePaginator;

    public function allActive(): Collection;

    /**
     * منتجات نشطة مع المخزون المتاح لشاشة نقطة البيع (تحميل مسبق).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function activeForPosCatalog(): Collection;

    public function findByBarcode(string $barcode): ?Product;

    public function findById(int $id): ?Product;

    public function create(array $data): Product;

    public function update(Product $product, array $data): Product;
}
