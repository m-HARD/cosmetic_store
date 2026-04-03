<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService
    ) {
    }

    public function dashboard(): Response
    {
        return Inertia::render('Dashboard/IndexPage', [
            'summary' => $this->reportService->dashboardSummary(),
        ]);
    }

    public function pos(): Response
    {
        return Inertia::render('POS/POSPage');
    }

    public function reports(): Response
    {
        return Inertia::render('Reports/IndexPage', [
            'summary' => $this->reportService->dashboardSummary(),
        ]);
    }
}
