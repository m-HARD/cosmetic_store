<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(1, (int) $request->integer('per_page', 20)));

        return response()->json($this->productRepository->paginate($perPage));
    }

    public function show(Product $product): JsonResponse
    {
        $product->load(['category', 'supplier']);
        $product->loadSum('batches as total_stock', 'remaining_quantity');

        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => ['required', 'string', 'max:100', 'unique:products,barcode'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sale_price' => ['required', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product = $this->productRepository->create($payload);

        return response()->json($product->load(['category', 'supplier']), 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'barcode' => ['sometimes', 'required', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($product->id)],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'sale_price' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_stock_alert' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $updated = $this->productRepository->update($product, $payload);

        return response()->json($updated->load(['category', 'supplier']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
