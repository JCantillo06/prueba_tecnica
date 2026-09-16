<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected ReportAnalyticsService $reportService;

    public function __construct(ReportAnalyticsService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * GET /api/reports/summary?import_id={X}
     * Returns BI summary KPIs, Top 5 Products, Category Breakdown, and Country Breakdown.
     */
    public function summary(Request $request): JsonResponse
    {
        $importId = $request->has('import_id') ? (int) $request->query('import_id') : null;

        $report = $this->reportService->getSummary($importId);

        if (isset($report['success']) && $report['success'] === false) {
            return response()->json($report, 404);
        }

        return response()->json($report);
    }
}
