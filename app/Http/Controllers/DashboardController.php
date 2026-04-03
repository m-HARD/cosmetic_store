<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    public function index(): JsonResponse
    {
        // ملخص موحّد للوحة التحكم لتقليل عدد الاستدعاءات من الواجهة.
        return response()->json($this->reportService->dashboardSummary());
    }
}
