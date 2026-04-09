<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CategoryController extends Controller
{
    /** قائمة الفئات النشطة لنماذج الإدخال ونقطة البيع. */
    public function index(): JsonResponse
    {
        $rows = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        return response()->json(Category::query()->create($payload), 201);
    }

    public function manageIndex(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'has_products' => ['nullable', 'in:all,yes,no'],
            'status' => ['nullable', 'in:all,active,inactive'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $query = Category::query()->withCount('products');

        if (!empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $hasProducts = $validated['has_products'] ?? 'all';
        if ($hasProducts === 'yes') {
            $query->has('products');
        } elseif ($hasProducts === 'no') {
            $query->doesntHave('products');
        }

        $status = $validated['status'] ?? 'all';
        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return response()->json(
            $query->latest('id')->paginate((int) ($validated['per_page'] ?? 15))
        );
    }

    public function show(Category $category): JsonResponse
    {
        $products = Product::query()
            ->where('category_id', $category->id)
            ->with('supplier:id,name')
            ->withSum('batches as total_stock', 'remaining_quantity')
            ->latest('id')
            ->get([
                'id',
                'name',
                'barcode',
                'supplier_id',
                'sale_price',
                'is_active',
            ]);

        return response()->json([
            'category' => $category,
            'products_count' => $products->count(),
            'products_stock_total' => (int) $products->sum(static fn (Product $product) => (int) ($product->total_stock ?? 0)),
            'products' => $products,
        ]);
    }

    public function update(Request $request, Category $category): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);

        $category->update($payload);

        return response()->json($category->refresh());
    }

    public function destroy(Request $request, Category $category): JsonResponse
    {
        $productsCount = Product::query()
            ->where('category_id', $category->id)
            ->count();

        if ($productsCount === 0) {
            $category->delete();

            return response()->json(['message' => 'تم حذف الفئة بنجاح.']);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string'],
            'transfer_category_id' => ['nullable', 'integer', 'exists:categories,id'],
        ]);

        $transferCategoryId = $validated['transfer_category_id'] ?? null;
        if ($transferCategoryId && (int) $transferCategoryId === (int) $category->id) {
            return response()->json([
                'message' => 'لا يمكن نقل المنتجات إلى نفس الفئة.',
                'errors' => ['transfer_category_id' => ['لا يمكن نقل المنتجات إلى نفس الفئة.']],
            ], 422);
        }

        if ($transferCategoryId) {
            Product::query()
                ->where('category_id', $category->id)
                ->update(['category_id' => (int) $transferCategoryId]);

            $category->delete();

            return response()->json(['message' => 'تم نقل المنتجات وحذف الفئة بنجاح.']);
        }

        $password = $validated['password'] ?? null;
        if (!$password || !Hash::check($password, (string) $request->user()?->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة.',
                'errors' => ['password' => ['كلمة المرور غير صحيحة.']],
            ], 422);
        }

        DB::transaction(function () use ($category): void {
            Product::query()
                ->where('category_id', $category->id)
                ->get()
                ->each
                ->delete();

            $category->delete();
        });

        return response()->json(['message' => 'تم حذف الفئة وجميع منتجاتها بنجاح.']);
    }
}
