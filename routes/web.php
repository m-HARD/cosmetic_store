<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\POSController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RoleManagementController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\UserManagementController;

Route::get('/', function () {
    return redirect('/dashboard');
});

Route::middleware(['role:SUPER ADMIN,INVENTORY MANAGER,CASHIER,ACCOUNTS'])->group(function (): void {
    Route::get('/dashboard', [PageController::class, 'dashboard']);
    Route::get('/pos', [PageController::class, 'pos']);
    Route::get('/reports', [PageController::class, 'reports']);
    Route::get('/api/dashboard', [DashboardController::class, 'index']);
    Route::get('/api/reports/financial', [ReportController::class, 'financialSummary']);
});

Route::middleware(['role:SUPER ADMIN,INVENTORY MANAGER'])->group(function (): void {
    Route::get('/api/products', [ProductController::class, 'index']);
    Route::post('/api/products', [ProductController::class, 'store']);
    Route::get('/api/suppliers', [SupplierController::class, 'index']);
    Route::post('/api/suppliers', [SupplierController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN,CASHIER'])->group(function (): void {
    Route::post('/api/pos/sales', [POSController::class, 'store']);
    Route::post('/api/refunds', [RefundController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN,ACCOUNTS'])->group(function (): void {
    Route::get('/api/expenses', [ExpenseController::class, 'index']);
    Route::post('/api/expenses', [ExpenseController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN'])->group(function (): void {
    Route::get('/api/users', [UserManagementController::class, 'index']);
    Route::put('/api/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/api/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/api/roles', [RoleManagementController::class, 'index']);
    Route::post('/api/roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions']);
});
