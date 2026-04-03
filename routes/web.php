<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;

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
    Route::get('/api/products', [ProductController::class, 'index']);
    Route::post('/api/products', [ProductController::class, 'store']);
    Route::get('/api/suppliers', [SupplierController::class, 'index']);
    Route::post('/api/suppliers', [SupplierController::class, 'store']);
    Route::get('/api/inventory/expiring', [InventoryController::class, 'expiringSoon']);
    Route::get('/api/inventory/low-stock', [InventoryController::class, 'lowStock']);
    Route::post('/api/inventory/losses', [InventoryController::class, 'markAsLoss']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,CASHIER'])->group(function (): void {
    Route::post('/api/pos/sales', [POSController::class, 'store']);
    Route::post('/api/refunds', [RefundController::class, 'store']);
});

Route::middleware(['auth', 'role:SUPER ADMIN,ACCOUNTS'])->group(function (): void {
    Route::get('/api/expenses', [ExpenseController::class, 'index']);
    Route::post('/api/expenses', [ExpenseController::class, 'store']);
});

Route::middleware(['auth', 'role:SUPER ADMIN'])->group(function (): void {
    Route::get('/api/users', [UserManagementController::class, 'index']);
    Route::put('/api/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/api/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/api/roles', [RoleManagementController::class, 'index']);
    Route::post('/api/roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions']);
});
