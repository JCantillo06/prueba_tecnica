<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UploadCsvRequest;
use App\Jobs\ProcessCsvImportJob;
use App\Models\Import;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportController extends Controller
{
    /**
     * GET /api/imports
     * List all imports chronologically with aggregate stats.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 15), 100);

        $imports = Import::query()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $imports->items(),
            'pagination' => [
                'total' => $imports->total(),
                'per_page' => $imports->perPage(),
                'current_page' => $imports->currentPage(),
                'last_page' => $imports->lastPage(),
            ],
            'stats' => [
                'total_imports' => Import::count(),
                'completed' => Import::where('status', 'completed')->count(),
                'processing' => Import::where('status', 'processing')->count(),
                'failed' => Import::where('status', 'failed')->count(),
                'total_records_processed' => (int) Import::sum('successful_rows'),
                'total_revenue_overall' => (float) Import::sum('total_revenue'),
            ],
        ]);
    }

    /**
     * POST /api/imports
     * Upload CSV and dispatch asynchronous processing. Immediate response (202 Accepted).
     */
    public function store(UploadCsvRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $storedName = 'imports/' . Str::uuid() . '_' . time() . '.csv';

        // Store file in local disk
        $path = $file->storeAs('', $storedName, 'local');

        // Create initial pending Import record
        $import = Import::create([
            'file_name' => $storedName,
            'original_name' => $originalName,
            'status' => 'pending',
            'total_rows' => 0,
            'successful_rows' => 0,
            'failed_rows' => 0,
            'total_revenue' => 0.00,
        ]);

        // Dispatch background ETL job
        ProcessCsvImportJob::dispatch($import);

        return response()->json([
            'success' => true,
            'message' => 'Archivo recibido exitosamente. El procesamiento se ha iniciado en segundo plano.',
            'data' => $import,
        ], 202);
    }

    /**
     * GET /api/imports/{id}
     * Get specific import detail and current status.
     */
    public function show(int $id): JsonResponse
    {
        $import = Import::withCount(['errors', 'saleRecords'])->find($id);

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Importación no encontrada.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $import,
        ]);
    }

    /**
     * GET /api/imports/{id}/errors
     * Paginated list of validation errors/inconsistencies.
     */
    public function errors(Request $request, int $id): JsonResponse
    {
        $import = Import::find($id);

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Importación no encontrada.',
            ], 404);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        $errors = $import->errors()
            ->orderBy('row_number', 'asc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'import_id' => $import->id,
            'file_name' => $import->original_name,
            'total_errors' => $errors->total(),
            'data' => $errors->items(),
            'pagination' => [
                'total' => $errors->total(),
                'per_page' => $errors->perPage(),
                'current_page' => $errors->currentPage(),
                'last_page' => $errors->lastPage(),
            ],
        ]);
    }

    /**
     * DELETE /api/imports/{id}
     * Cascade delete of import, sales records, and errors.
     */
    public function destroy(int $id): JsonResponse
    {
        $import = Import::find($id);

        if (!$import) {
            return response()->json([
                'success' => false,
                'message' => 'Importación no encontrada.',
            ], 404);
        }

        DB::transaction(function () use ($import) {
            // Delete physical file if still exists
            if (Storage::disk('local')->exists($import->file_name)) {
                Storage::disk('local')->delete($import->file_name);
            }

            // Cascade delete will delete sale_records and import_errors automatically via DB FKs
            $import->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'La importación, sus registros de ventas y sus errores vinculados han sido eliminados íntegramente.',
        ]);
    }
}
