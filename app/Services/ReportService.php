<?php

namespace App\Services;

use App\Repositories\Contracts\DashboardRepositoryInterface;

class ReportService
{
    public function __construct(
        private readonly DashboardRepositoryInterface $dashboardRepository
    ) {
    }

    public function dashboardSummary(): array
    {
        return [
            'today_sales' => $this->dashboardRepository->todaySalesTotal(),
            'month_sales' => $this->dashboardRepository->monthSalesTotal(),
            'total_expenses' => $this->dashboardRepository->totalExpenses(),
            'total_losses' => $this->dashboardRepository->totalLosses(),
            'net_profit' => $this->dashboardRepository->netProfit(),
            'low_stock_count' => $this->dashboardRepository->lowStockCount(),
            'expiring_products_count' => $this->dashboardRepository->expiringProductsCount(),
        ];
    }
}
