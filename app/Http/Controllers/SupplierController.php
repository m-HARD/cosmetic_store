<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SupplierController extends Controller
{
    /** قائمة مبسطة للقوائم المنسدلة. */
    public function options(): JsonResponse
    {
        $rows = Supplier::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($rows);
    }

    public function index(): JsonResponse
    {
        $validated = request()->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'has_products' => ['nullable', 'in:all,yes,no'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = Supplier::query()->withCount('products');

        if (!empty($validated['search'])) {
            $term = trim($validated['search']);
            $query->where(function ($q) use ($term): void {
                $q->where('name', 'like', "%{$term}%")
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%");
            });
        }

        $hasProducts = $validated['has_products'] ?? 'all';
        if ($hasProducts === 'yes') {
            $query->has('products');
        } elseif ($hasProducts === 'no') {
            $query->doesntHave('products');
        }

        $perPage = (int) ($validated['per_page'] ?? 15);

        return response()->json(
            $query->latest('id')->paginate($perPage)
        );
    }

    public function show(Supplier $supplier): JsonResponse
    {
        $products = Product::query()
            ->where('supplier_id', $supplier->id)
            ->with('category:id,name')
            ->withSum('batches as total_stock', 'remaining_quantity')
            ->latest('id')
            ->get([
                'id',
                'name',
                'barcode',
                'category_id',
                'sale_price',
                'is_active',
            ]);

        return response()->json([
            'supplier' => $supplier,
            'products_count' => $products->count(),
            'products_stock_total' => (int) $products->sum(static fn (Product $product) => (int) ($product->total_stock ?? 0)),
            'products' => $products,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        return response()->json(Supplier::query()->create($payload), 201);
    }

    public function update(Request $request, Supplier $supplier): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $supplier->update($payload);

        return response()->json($supplier->refresh());
    }

    public function destroy(Request $request, Supplier $supplier): JsonResponse
    {
        $productsCount = Product::query()
            ->where('supplier_id', $supplier->id)
            ->count();

        if ($productsCount === 0) {
            $supplier->delete();

            return response()->json(['message' => 'تم حذف المورد بنجاح.']);
        }

        $validated = $request->validate([
            'password' => ['nullable', 'string'],
            'transfer_supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
        ]);

        $transferSupplierId = $validated['transfer_supplier_id'] ?? null;
        if ($transferSupplierId && (int) $transferSupplierId === (int) $supplier->id) {
            return response()->json([
                'message' => 'لا يمكن نقل المنتجات إلى نفس المورد.',
                'errors' => ['transfer_supplier_id' => ['لا يمكن نقل المنتجات إلى نفس المورد.']],
            ], 422);
        }

        if ($transferSupplierId) {
            Product::query()
                ->where('supplier_id', $supplier->id)
                ->update(['supplier_id' => (int) $transferSupplierId]);

            $supplier->delete();

            return response()->json(['message' => 'تم نقل المنتجات وحذف المورد بنجاح.']);
        }

        $password = $validated['password'] ?? null;
        if (!$password || !Hash::check($password, (string) $request->user()?->password)) {
            return response()->json([
                'message' => 'كلمة المرور غير صحيحة.',
                'errors' => ['password' => ['كلمة المرور غير صحيحة.']],
            ], 422);
        }

        DB::transaction(function () use ($supplier): void {
            Product::query()
                ->where('supplier_id', $supplier->id)
                ->get()
                ->each
                ->delete();

            $supplier->delete();
        });

        return response()->json(['message' => 'تم حذف المورد وجميع منتجاته بنجاح.']);
    }
}
