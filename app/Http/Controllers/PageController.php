<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ProductRepositoryInterface $productRepository
    ) {}

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard/IndexPage', [
            'summary' => $this->reportService->dashboardSummary(),
        ]);
    }

    public function pos(): Response
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('POS/POSPage', [
            'categories' => $categories,
            'products' => $this->productRepository->activeForPosCatalog(),
        ]);
    }

    public function reports(): Response
    {
        return Inertia::render('Reports/IndexPage', [
            'summary' => $this->reportService->dashboardSummary(),
        ]);
    }

    public function inventory(): Response
    {
        return Inertia::render('Inventory/IndexPage');
    }

    public function suppliers(): Response
    {
        return Inertia::render('Suppliers/IndexPage');
    }

    public function supplierShow(Supplier $supplier): Response
    {
        return Inertia::render('Suppliers/ShowPage', [
            'supplierId' => $supplier->id,
        ]);
    }

    public function expenses(): Response
    {
        return Inertia::render('Expenses/IndexPage');
    }

    public function refunds(): Response
    {
        return Inertia::render('Refunds/IndexPage');
    }

    public function products(): Response
    {
        return Inertia::render('Products/IndexPage');
    }

    public function productShow(Product $product): Response
    {
        return Inertia::render('Products/ShowPage', [
            'productId' => $product->id,
        ]);
    }

    public function productCreate(): Response
    {
        return Inertia::render('Products/CreatePage');
    }

    public function categories(): Response
    {
        return Inertia::render('Categories/IndexPage');
    }

    public function categoryShow(Category $category): Response
    {
        return Inertia::render('Categories/ShowPage', [
            'categoryId' => $category->id,
        ]);
    }

    public function users(): Response
    {
        return Inertia::render('Users/IndexPage');
    }
}
