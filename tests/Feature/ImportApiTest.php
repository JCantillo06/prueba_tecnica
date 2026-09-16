<?php

namespace Tests\Feature;

use App\Jobs\ProcessCsvImportJob;
use App\Models\Import;
use App\Models\ImportError;
use App\Models\SaleRecord;
use App\Services\CsvImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_upload_csv_and_receive_immediate_202_accepted_response(): void
    {
        Queue::fake();
        Storage::fake('local');

        $csvContent = "order_id,date,customer_id,customer_name,product_id,product_name,category,quantity,unit_price,discount,country\n";
        $csvContent .= "1001,2024-01-03,C042,Empresa ABC,P018,Widget Pro,Electronics,2,149.99,0.10,Colombia\n";

        $file = UploadedFile::fake()->createWithContent('sales_test.csv', $csvContent);

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(202)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'original_name',
                    'status',
                ],
            ]);

        $this->assertDatabaseHas('imports', [
            'original_name' => 'sales_test.csv',
            'status' => 'pending',
        ]);

        Queue::assertPushed(ProcessCsvImportJob::class);
    }

    public function test_rejects_invalid_file_format(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('document.pdf', 100, 'application/pdf');

        $response = $this->postJson('/api/imports', [
            'file' => $file,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_can_list_imports_chronologically(): void
    {
        $import1 = Import::factory()->create(['created_at' => now()->subHours(2)]);
        $import2 = Import::factory()->create(['created_at' => now()->subHour()]);

        $response = $this->getJson('/api/imports');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data',
                'pagination',
                'stats',
            ]);
    }

    public function test_can_retrieve_import_errors_paginated(): void
    {
        $import = Import::create([
            'file_name' => 'imports/test.csv',
            'original_name' => 'test.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
            'total_revenue' => 100.00,
        ]);

        ImportError::create([
            'import_id' => $import->id,
            'row_number' => 2,
            'raw_data' => ['order_id' => '1002'],
            'error_reason' => 'El precio unitario no puede ser negativo.',
        ]);

        $response = $this->getJson("/api/imports/{$import->id}/errors");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'import_id' => $import->id,
                'total_errors' => 1,
            ])
            ->assertJsonFragment([
                'row_number' => 2,
                'error_reason' => 'El precio unitario no puede ser negativo.',
            ]);
    }

    public function test_cascade_deletion_removes_import_sales_and_errors(): void
    {
        Storage::fake('local');
        $storedPath = 'imports/test_delete.csv';
        Storage::disk('local')->put($storedPath, 'content');

        $import = Import::create([
            'file_name' => $storedPath,
            'original_name' => 'test_delete.csv',
            'status' => 'completed',
            'total_rows' => 2,
            'successful_rows' => 1,
            'failed_rows' => 1,
            'total_revenue' => 269.98,
        ]);

        $sale = SaleRecord::create([
            'import_id' => $import->id,
            'order_id' => '1001',
            'date' => '2024-01-03',
            'customer_id' => 'C042',
            'customer_name' => 'Empresa ABC',
            'product_id' => 'P018',
            'product_name' => 'Widget Pro',
            'category' => 'Electronics',
            'quantity' => 2,
            'unit_price' => 149.99,
            'discount' => 0.10,
            'total_amount' => 269.98,
            'country' => 'Colombia',
        ]);

        $error = ImportError::create([
            'import_id' => $import->id,
            'row_number' => 3,
            'raw_data' => ['order_id' => '1002'],
            'error_reason' => 'Cantidad en cero.',
        ]);

        $response = $this->deleteJson("/api/imports/{$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('imports', ['id' => $import->id]);
        $this->assertDatabaseMissing('sale_records', ['id' => $sale->id]);
        $this->assertDatabaseMissing('import_errors', ['id' => $error->id]);
        Storage::disk('local')->assertMissing($storedPath);
    }
}
