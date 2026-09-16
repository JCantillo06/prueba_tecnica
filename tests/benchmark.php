<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Import;
use App\Services\CsvImportService;
use App\Services\ReportAnalyticsService;

echo "=== BENCHMARK ETL & BI ANALYTICS (25,000 REGISTROS) ===\n";

$filePath = storage_path('app/samples/benchmark_25k.csv');
if (!file_exists($filePath)) {
    echo "Archivo no encontrado: {$filePath}\n";
    exit(1);
}

$import = Import::create([
    'file_name' => 'samples/benchmark_25k.csv',
    'original_name' => 'benchmark_25k.csv',
    'status' => 'pending',
]);

$startMemory = memory_get_usage(true);
$startTime = microtime(true);

$service = new CsvImportService();
$service->process($import, $filePath);

$endTime = microtime(true);
$peakMemory = memory_get_peak_usage(true);

$import->refresh();

echo "-> Estado de Importación: {$import->status}\n";
echo "-> Filas Totales Procesadas: " . number_format($import->total_rows) . "\n";
echo "-> Registros Válidos: " . number_format($import->successful_rows) . "\n";
echo "-> Inconsistencias (Errores Omitidos): " . number_format($import->failed_rows) . "\n";
echo "-> Ingresos Calculados: $" . number_format($import->total_revenue, 2) . "\n";
echo "-> Tiempo de Ingesta y Persistencia: " . round($endTime - $startTime, 2) . " segundos\n";
echo "-> Memoria RAM Pico: " . round($peakMemory / 1024 / 1024, 2) . " MB\n";

echo "\n=== BENCHMARK CONSULTA BI REPORT (AGREGACIONES) ===\n";
$reportStart = microtime(true);
$reportService = new ReportAnalyticsService();
$summary = $reportService->getSummary($import->id);
$reportEnd = microtime(true);

echo "-> Tiempo de Generación de Reporte BI: " . round(($reportEnd - $reportStart) * 1000, 2) . " ms\n";
echo "-> Top 5 Productos Identificados:\n";
foreach ($summary['top_products'] as $idx => $prod) {
    echo "   #" . ($idx + 1) . " {$prod['product_name']} ({$prod['product_id']}): $" . number_format($prod['total_revenue'], 2) . " ({$prod['units_sold']} uds)\n";
}

echo "-> Distribución por Categorías (" . count($summary['category_distribution']) . " categorías):\n";
foreach ($summary['category_distribution'] as $cat) {
    echo "   - {$cat['category']}: $" . number_format($cat['total_revenue'], 2) . " ({$cat['percentage']}%)\n";
}

echo "-> Distribución por Países (" . count($summary['geographical_distribution']) . " países):\n";
foreach ($summary['geographical_distribution'] as $country) {
    echo "   - {$country['country']}: $" . number_format($country['total_revenue'], 2) . " ({$country['percentage']}%)\n";
}
echo "\n¡Benchmark completado con éxito rotundo!\n";
