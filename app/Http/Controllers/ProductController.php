<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'search' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'has_batches' => ['nullable', 'in:all,yes,no'],
            'stock_state' => ['nullable', 'in:all,low,available,out'],
        ]);

        $perPage = (int) ($validated['per_page'] ?? 15);

        $query = Product::query()
            ->with(['category', 'supplier'])
            ->withSum('batches as total_stock', 'remaining_quantity');

        if (!empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('barcode', 'like', "%{$term}%");
            });
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', (int) $validated['category_id']);
        }

        if (!empty($validated['supplier_id'])) {
            $query->where('supplier_id', (int) $validated['supplier_id']);
        }

        $hasBatches = $validated['has_batches'] ?? 'all';
        if ($hasBatches === 'yes') {
            $query->has('batches');
        } elseif ($hasBatches === 'no') {
            $query->doesntHave('batches');
        }

        $stockState = $validated['stock_state'] ?? 'all';
        if ($stockState === 'out') {
            $query->havingRaw('COALESCE(total_stock, 0) = 0');
        } elseif ($stockState === 'available') {
            $query->havingRaw('COALESCE(total_stock, 0) > 0');
        } elseif ($stockState === 'low') {
            $query->havingRaw('COALESCE(total_stock, 0) <= min_stock_alert');
        }

        return response()->json($query->latest('id')->paginate($perPage));
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
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $payload['image'] = $request->file('image')->store('products', 'public');
        }

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
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('remove_image') && $product->image) {
            Storage::disk('public')->delete($product->image);
            $payload['image'] = null;
        }

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $payload['image'] = $request->file('image')->store('products', 'public');
        }

        $updated = $this->productRepository->update($product, $payload);

        return response()->json($updated->load(['category', 'supplier']));
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json(null, 204);
    }
}
