<?php

namespace App\Repositories;

use App\Models\Expense;
use App\Models\Loss;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\Sale;
use App\Repositories\Contracts\DashboardRepositoryInterface;
use Carbon\Carbon;

class DashboardRepository implements DashboardRepositoryInterface
{
    public function todaySalesTotal(): float
    {
        return (float) Sale::query()
            ->whereDate('sold_at', Carbon::today())
            ->sum('total');
    }

    public function monthSalesTotal(): float
    {
        return (float) Sale::query()
            ->whereYear('sold_at', Carbon::today()->year)
            ->whereMonth('sold_at', Carbon::today()->month)
            ->sum('total');
    }

    public function totalExpenses(): float
    {
        return (float) Expense::query()->sum('amount');
    }

    public function totalLosses(): float
    {
        return (float) Loss::query()->sum('loss_value');
    }

    public function netProfit(): float
    {
        return $this->monthSalesTotal() - $this->totalExpenses() - $this->totalLosses();
    }

    public function lowStockCount(): int
    {
        return Product::query()
            ->whereRaw('(SELECT COALESCE(SUM(remaining_quantity), 0) FROM product_batches WHERE product_batches.product_id = products.id) <= min_stock_alert')
            ->count();
    }

    public function expiringProductsCount(int $days = 30): int
    {
        return ProductBatch::query()
            ->whereDate('expiration_date', '<=', Carbon::today()->addDays($days))
            ->where('remaining_quantity', '>', 0)
            ->distinct('product_id')
            ->count('product_id');
    }
}
