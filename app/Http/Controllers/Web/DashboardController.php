<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Import;
use App\Services\ReportAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    protected ReportAnalyticsService $reportService;

    public function __construct(ReportAnalyticsService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Display the main Dashboard with import history and global metrics.
     */
    public function index(Request $request)
    {
        $imports = Import::query()
            ->withCount(['errors', 'saleRecords'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $globalReport = $this->reportService->getSummary(null);

        $stats = [
            'total_imports' => Import::count(),
            'completed_imports' => Import::where('status', 'completed')->count(),
            'processing_imports' => Import::where('status', 'processing')->count(),
            'failed_imports' => Import::where('status', 'failed')->count(),
            'total_rows_processed' => (int) Import::sum('successful_rows'),
            'total_revenue' => (float) Import::sum('total_revenue'),
            'total_errors' => (int) Import::sum('failed_rows'),
        ];

        return view('dashboard', compact('imports', 'stats', 'globalReport'));
    }

    /**
     * Display the detail of valid records and inconsistencies with filtering.
     */
    public function detail(Request $request, int $id)
    {
        $import = Import::withCount(['errors', 'saleRecords'])->findOrFail($id);

        if ($request->filled('tab')) {
            $activeTab = $request->query('tab');
        } elseif ($request->filled('errors_page') || $request->filled('error_search')) {
            $activeTab = 'errors';
        } else {
            $activeTab = 'valid';
        }

        // Query for valid records
        $recordsQuery = $import->saleRecords();

        if ($request->filled('customer')) {
            $customerSearch = trim($request->query('customer'));
            $recordsQuery->where(function ($q) use ($customerSearch) {
                $q->where('customer_name', 'LIKE', "%{$customerSearch}%")
                  ->orWhere('customer_id', 'LIKE', "%{$customerSearch}%");
            });
        }

        if ($request->filled('product')) {
            $productSearch = trim($request->query('product'));
            $recordsQuery->where(function ($q) use ($productSearch) {
                $q->where('product_name', 'LIKE', "%{$productSearch}%")
                  ->orWhere('product_id', 'LIKE', "%{$productSearch}%");
            });
        }

        if ($request->filled('category')) {
            $recordsQuery->where('category', $request->query('category'));
        }

        $allowedSort = ['date', 'total_amount', 'quantity', 'unit_price', 'order_id', 'id'];
        $sortBy = in_array($request->query('sort_by'), $allowedSort) ? $request->query('sort_by') : 'id';
        $sortDir = strtolower($request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $recordsQuery->orderBy($sortBy, $sortDir);

        $records = $recordsQuery->paginate(20, ['*'], 'records_page')->withQueryString();

        // Query for errors
        $errorsQuery = $import->errors();
        if ($request->filled('error_search')) {
            $search = trim($request->query('error_search'));
            $errorsQuery->where(function ($q) use ($search) {
                $q->where('error_reason', 'LIKE', "%{$search}%")
                  ->orWhere('raw_data', 'LIKE', "%{$search}%");
            });
        }
        $errors = $errorsQuery->orderBy('row_number', 'asc')->paginate(20, ['*'], 'errors_page')->withQueryString();

        $categories = $import->saleRecords()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter()
            ->values();

        return view('imports.detail', compact('import', 'records', 'errors', 'categories', 'activeTab'));
    }

    /**
     * Display BI visual report for a specific import.
     */
    public function report(int $id)
    {
        $import = Import::withCount(['errors', 'saleRecords'])->findOrFail($id);
        $report = $this->reportService->getSummary($import->id);

        return view('imports.report', compact('import', 'report'));
    }

    /**
     * Backward compatibility method for show.
     */
    public function show(int $id)
    {
        return $this->report($id);
    }

    /**
     * Generate a synthetic sample CSV file on-demand and trigger download.
     */
    public function generateSample(Request $request)
    {
        $rows = min((int) $request->query('rows', 25000), 100000);
        $errors = (int) $request->query('errors', 200);
        $filename = "sample_sales_{$rows}_rows.csv";
        $relativePath = "storage/app/samples/{$filename}";
        $fullPath = base_path($relativePath);

        Artisan::call('sales:generate-csv', [
            '--rows' => $rows,
            '--errors' => $errors,
            '--output' => $relativePath,
        ]);

        if (file_exists($fullPath)) {
            return response()->download($fullPath, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        }

        return redirect()->route('dashboard')->with('error', 'No se pudo generar el archivo de muestra.');
    }
}
