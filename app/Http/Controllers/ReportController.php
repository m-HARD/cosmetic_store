<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    public function financialSummary(): JsonResponse
    {
        return response()->json($this->reportService->dashboardSummary());
    }
}
