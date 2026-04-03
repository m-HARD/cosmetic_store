<?php

namespace App\Repositories\Contracts;

interface DashboardRepositoryInterface
{
    public function todaySalesTotal(): float;

    public function monthSalesTotal(): float;

    public function totalExpenses(): float;

    public function totalLosses(): float;

    public function netProfit(): float;

    public function lowStockCount(): int;

    public function expiringProductsCount(int $days = 30): int;
}
