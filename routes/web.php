<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\POSController;
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
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/reports/financial', [ReportController::class, 'financialSummary']);
});

Route::middleware(['role:SUPER ADMIN,INVENTORY MANAGER'])->group(function (): void {
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN,CASHIER'])->group(function (): void {
    Route::post('/pos/sales', [POSController::class, 'store']);
    Route::post('/refunds', [RefundController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN,ACCOUNTS'])->group(function (): void {
    Route::get('/expenses', [ExpenseController::class, 'index']);
    Route::post('/expenses', [ExpenseController::class, 'store']);
});

Route::middleware(['role:SUPER ADMIN'])->group(function (): void {
    Route::get('/users', [UserManagementController::class, 'index']);
    Route::put('/users/{user}', [UserManagementController::class, 'update']);
    Route::delete('/users/{user}', [UserManagementController::class, 'destroy']);

    Route::get('/roles', [RoleManagementController::class, 'index']);
    Route::post('/roles/{role}/permissions', [RoleManagementController::class, 'syncPermissions']);
});
