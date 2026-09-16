<?php

namespace App\Jobs;

use App\Models\Import;
use App\Services\CsvImportService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCsvImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600; // 10 minutes timeout for very large files
    public int $tries = 3;

    protected Import $import;
    protected ?string $filePath;

    /**
     * Create a new job instance.
     */
    public function __construct(Import $import, ?string $filePath = null)
    {
        $this->import = $import;
        $this->filePath = $filePath;
    }

    /**
     * Execute the job.
     */
    public function handle(CsvImportService $importService): void
    {
        Log::info("Iniciando procesamiento de archivo CSV para Import ID {$this->import->id}");
        $importService->process($this->import, $this->filePath);
        Log::info("Finalizado procesamiento de archivo CSV para Import ID {$this->import->id}");
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Fallo definitivo en Job de Importación ID {$this->import->id}: " . $exception->getMessage());

        $this->import->update([
            'status' => 'failed',
            'error_message' => 'Fallo en la cola de procesamiento: ' . $exception->getMessage(),
            'completed_at' => now(),
        ]);
    }
}
