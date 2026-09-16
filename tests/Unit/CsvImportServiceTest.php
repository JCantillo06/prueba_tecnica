<?php

namespace Tests\Unit;

use App\Models\Import;
use App\Services\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CsvImportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_service_calculates_correct_totals_and_logs_inconsistencies(): void
    {
        Storage::fake('local');
        $csvPath = 'imports/test_etl.csv';

        // CSV with 2 valid rows and 3 invalid rows (negative price, zero quantity, bad date)
        $csvLines = [
            'order_id,date,customer_id,customer_name,product_id,product_name,category,quantity,unit_price,discount,country',
            '1001,2024-01-03,C042,Empresa ABC,P018,Widget Pro,Electronics,2,100.00,0.10,Colombia', // total: 2 * 100 * 0.9 = 180.00
            '1002,2024-01-04,C043,Empresa XYZ,P019,Widget Plus,Electronics,1,50.00,0.00,México',    // total: 1 * 50 * 1 = 50.00
            '1003,2024-01-05,C044,Empresa Bad,P020,Bad Price,Home,2,-25.00,0.00,Chile',            // Negative price -> ERROR
            '1004,2024-01-06,C045,Empresa Zero,P021,Zero Qty,Home,0,30.00,0.00,Perú',              // Zero qty -> ERROR
            '1005,invalid-date,C046,Empresa Date,P022,Bad Date,Clothing,1,20.00,0.00,Colombia',     // Bad date -> ERROR
        ];

        Storage::disk('local')->put($csvPath, implode("\n", $csvLines));

        $import = Import::create([
            'file_name' => $csvPath,
            'original_name' => 'test_etl.csv',
            'status' => 'pending',
        ]);

        $service = new CsvImportService();
        $service->process($import, Storage::disk('local')->path($csvPath));

        $import->refresh();

        $this->assertEquals('completed', $import->status);
        $this->assertEquals(5, $import->total_rows);
        $this->assertEquals(2, $import->successful_rows);
        $this->assertEquals(3, $import->failed_rows);
        $this->assertEquals(230.00, (float) $import->total_revenue);

        // Check sales records in DB
        $this->assertDatabaseCount('sale_records', 2);
        $this->assertDatabaseHas('sale_records', [
            'order_id' => '1001',
            'total_amount' => 180.00,
        ]);
        $this->assertDatabaseHas('sale_records', [
            'order_id' => '1002',
            'total_amount' => 50.00,
        ]);

        // Check error records in DB
        $this->assertDatabaseCount('import_errors', 3);
        $this->assertDatabaseHas('import_errors', [
            'import_id' => $import->id,
            'row_number' => 4, // 1003
        ]);
        $this->assertDatabaseHas('import_errors', [
            'import_id' => $import->id,
            'row_number' => 5, // 1004
        ]);
        $this->assertDatabaseHas('import_errors', [
            'import_id' => $import->id,
            'row_number' => 6, // 1005
        ]);
    }
}
