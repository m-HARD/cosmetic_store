<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\ProductBatchController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect('/dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// يجب أن يسبق وسيط الدور وسيط المصادقة؛ وإلا يُعاد 401 بدل التوجيه لصفحة الدخول.
Route::middleware(['auth', 'role:SUPER ADMIN,INVENTORY MANAGER,CASHIER,ACCOUNTS'])->group(function (): void {
    Route::get('/dashboard', [PageController::class, 'dashboard']);
    Route::get('/pos', [PageController::class, 'pos']);
    Route::get('/reports', [PageController::class, 'reports']);
    Route::get('/api/dashboard', [DashboardController::class, 'index']);
    Route::get('/api/reports/financial', [ReportController::class, 'financialSummary']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,INVENTORY MANAGER'])->group(function (): void {
    Route::get('/inventory', [PageController::class, 'inventory']);
    Route::get('/suppliers', [PageController::class, 'suppliers']);
    Route::get('/suppliers/{supplier}', [PageController::class, 'supplierShow']);
    Route::get('/categories', [PageController::class, 'categories']);
    Route::get('/categories/{category}', [PageController::class, 'categoryShow']);
    Route::get('/products', [PageController::class, 'products']);
    Route::get('/products/create', [PageController::class, 'productCreate']);
    Route::get('/products/{product}', [PageController::class, 'productShow']);
    Route::get('/api/categories', [CategoryController::class, 'index']);
    Route::get('/api/categories/manage', [CategoryController::class, 'manageIndex']);
    Route::get('/api/categories/{category}', [CategoryController::class, 'show']);
    Route::post('/api/categories', [CategoryController::class, 'store']);
    Route::put('/api/categories/{category}', [CategoryController::class, 'update']);
    Route::delete('/api/categories/{category}', [CategoryController::class, 'destroy']);
    Route::get('/api/products', [ProductController::class, 'index']);
    Route::post('/api/products', [ProductController::class, 'store']);
    Route::get('/api/products/{product}', [ProductController::class, 'show']);
    Route::put('/api/products/{product}', [ProductController::class, 'update']);
    Route::delete('/api/products/{product}', [ProductController::class, 'destroy']);
    Route::get('/api/products/{product}/batches', [ProductBatchController::class, 'index']);
    Route::post('/api/products/{product}/batches', [ProductBatchController::class, 'store']);
    Route::patch('/api/batches/{batch}', [ProductBatchController::class, 'update']);
    Route::get('/api/suppliers', [SupplierController::class, 'index']);
    Route::get('/api/suppliers/options', [SupplierController::class, 'options']);
    Route::get('/api/suppliers/{supplier}', [SupplierController::class, 'show']);
    Route::post('/api/suppliers', [SupplierController::class, 'store']);
    Route::put('/api/suppliers/{supplier}', [SupplierController::class, 'update']);
    Route::delete('/api/suppliers/{supplier}', [SupplierController::class, 'destroy']);
    Route::get('/api/inventory/expiring', [InventoryController::class, 'expiringSoon']);
    Route::get('/api/inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::get('/api/inventory/batches', [InventoryController::class, 'batches']);
    Route::post('/api/inventory/losses', [InventoryController::class, 'markAsLoss']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,INVENTORY MANAGER,CASHIER'])->group(function (): void {
    Route::post('/api/pos/sales', [POSController::class, 'store']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,CASHIER'])->group(function (): void {
    Route::get('/refunds', [PageController::class, 'refunds']);
    Route::post('/api/refunds', [RefundController::class, 'store']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,ACCOUNTS'])->group(function (): void {
    Route::get('/expenses', [PageController::class, 'expenses']);
    Route::get('/api/expenses', [ExpenseController::class, 'index']);
    Route::post('/api/expenses', [ExpenseController::class, 'store']);
});

Route::middleware(['auth', 'role:SUPER ADMIN'])->group(function (): void {
    Route::get('/users', [PageController::class, 'users']);
    Route::get('/api/users', [UserManagementController::class, 'index']);
    Route::put('/api/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/api/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/api/roles', [RoleManagementController::class, 'index']);
    Route::post('/api/roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions']);
});
