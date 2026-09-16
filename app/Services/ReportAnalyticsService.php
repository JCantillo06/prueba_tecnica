<?php

namespace App\Services;

use App\Models\Import;
use App\Models\SaleRecord;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportAnalyticsService
{
    /**
     * Generate BI report summary for a specific import or globally.
     */
    public function getSummary(?int $importId = null): array
    {
        $cacheKey = "report_summary_" . ($importId ?: 'all');

        // Check if import is completed and can be cached for fast retrieval
        if ($importId) {
            $import = Import::find($importId);
            if (!$import) {
                return [
                    'success' => false,
                    'message' => 'Importación no encontrada.',
                ];
            }

            if ($import->isCompleted()) {
                return Cache::remember($cacheKey, 300, function () use ($importId, $import) {
                    return $this->computeSummary($importId, $import);
                });
            }
        }

        return $this->computeSummary($importId);
    }

    /**
     * Compute summary metrics and breakdowns.
     */
    private function computeSummary(?int $importId = null, ?Import $import = null): array
    {
        $query = SaleRecord::query();
        if ($importId) {
            $query->where('import_id', $importId);
        }

        // 1. Total Revenue & Basic KPIs
        $kpiData = (clone $query)
            ->selectRaw('
                COALESCE(SUM(total_amount), 0) as total_revenue,
                COALESCE(SUM(quantity), 0) as total_units_sold,
                COUNT(*) as total_sales_records,
                COALESCE(AVG(total_amount), 0) as average_order_value,
                MIN(date) as min_date,
                MAX(date) as max_date
            ')
            ->first();

        $totalRevenue = (float) ($kpiData->total_revenue ?? 0);
        $totalUnits = (int) ($kpiData->total_units_sold ?? 0);
        $totalRecords = (int) ($kpiData->total_sales_records ?? 0);
        $avgOrderValue = (float) ($kpiData->average_order_value ?? 0);

        // 2. Top 5 Products by Revenue
        $topProducts = (clone $query)
            ->select(
                'product_id',
                'product_name',
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('AVG(unit_price) as avg_unit_price')
            )
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('total_revenue')
            ->limit(5)
            ->get()
            ->map(function ($row) use ($totalRevenue) {
                $rev = (float) $row->total_revenue;
                return [
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name,
                    'total_revenue' => round($rev, 2),
                    'units_sold' => (int) $row->units_sold,
                    'avg_unit_price' => round((float) $row->avg_unit_price, 2),
                    'revenue_percentage' => $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 2) : 0,
                ];
            });

        // 3. Category Distribution
        $categoryDistribution = (clone $query)
            ->select(
                'category',
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('COUNT(*) as transactions_count')
            )
            ->groupBy('category')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($row) use ($totalRevenue) {
                $rev = (float) $row->total_revenue;
                return [
                    'category' => $row->category,
                    'total_revenue' => round($rev, 2),
                    'units_sold' => (int) $row->units_sold,
                    'transactions_count' => (int) $row->transactions_count,
                    'percentage' => $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 2) : 0,
                ];
            });

        // 4. Geographical Distribution (by Country)
        $countryDistribution = (clone $query)
            ->select(
                'country',
                DB::raw('SUM(total_amount) as total_revenue'),
                DB::raw('SUM(quantity) as units_sold'),
                DB::raw('COUNT(*) as transactions_count')
            )
            ->groupBy('country')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($row) use ($totalRevenue) {
                $rev = (float) $row->total_revenue;
                return [
                    'country' => $row->country,
                    'total_revenue' => round($rev, 2),
                    'units_sold' => (int) $row->units_sold,
                    'transactions_count' => (int) $row->transactions_count,
                    'percentage' => $totalRevenue > 0 ? round(($rev / $totalRevenue) * 100, 2) : 0,
                ];
            });

        // Import metadata if specific import
        $importInfo = null;
        if ($importId) {
            $import = $import ?: Import::find($importId);
            if ($import) {
                $importInfo = [
                    'id' => $import->id,
                    'file_name' => $import->original_name,
                    'status' => $import->status,
                    'total_rows' => $import->total_rows,
                    'successful_rows' => $import->successful_rows,
                    'failed_rows' => $import->failed_rows,
                    'started_at' => $import->started_at?->toIso8601String(),
                    'completed_at' => $import->completed_at?->toIso8601String(),
                    'processing_time_seconds' => ($import->started_at && $import->completed_at)
                        ? $import->started_at->diffInSeconds($import->completed_at)
                        : null,
                ];
            }
        }

        return $this->sanitizeUtf8([
            'success' => true,
            'import' => $importInfo,
            'summary' => [
                'total_revenue' => round($totalRevenue, 2),
                'total_units_sold' => $totalUnits,
                'total_transactions' => $totalRecords,
                'average_order_value' => round($avgOrderValue, 2),
                'date_range' => [
                    'start' => $kpiData->min_date,
                    'end' => $kpiData->max_date,
                ],
            ],
            'top_products' => $topProducts->toArray(),
            'category_distribution' => $categoryDistribution->toArray(),
            'geographical_distribution' => $countryDistribution->toArray(),
        ]);
    }

    /**
     * Recursively sanitize all data to ensure valid UTF-8.
     */
    private function sanitizeUtf8(mixed $data): mixed
    {
        if (is_string($data)) {
            if (!mb_check_encoding($data, 'UTF-8')) {
                return mb_convert_encoding($data, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
            }
            return $data;
        }

        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $value) {
                $cleanedKey = is_string($key) && !mb_check_encoding($key, 'UTF-8')
                    ? mb_convert_encoding($key, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8')
                    : $key;
                $cleaned[$cleanedKey] = $this->sanitizeUtf8($value);
            }
            return $cleaned;
        }

        return $data;
    }
}
