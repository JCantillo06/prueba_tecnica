<?php

namespace Tests\Feature;

use App\Models\Import;
use App\Models\SaleRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_bi_summary_report_with_correct_formula_and_breakdowns(): void
    {
        $import = Import::create([
            'file_name' => 'imports/test_report.csv',
            'original_name' => 'test_report.csv',
            'status' => 'completed',
            'total_rows' => 3,
            'successful_rows' => 3,
            'failed_rows' => 0,
            'total_revenue' => 500.00,
        ]);

        // Record 1: Electronics in Colombia, total = 2 * 100 * (1 - 0.10) = 180.00
        SaleRecord::create([
            'import_id' => $import->id,
            'order_id' => 'ORD-1',
            'date' => '2024-01-10',
            'customer_id' => 'C1',
            'customer_name' => 'Customer A',
            'product_id' => 'P1',
            'product_name' => 'Phone X',
            'category' => 'Electronics',
            'quantity' => 2,
            'unit_price' => 100.00,
            'discount' => 0.10,
            'total_amount' => 180.00,
            'country' => 'Colombia',
        ]);

        // Record 2: Electronics in Mexico, total = 1 * 200 * (1 - 0.00) = 200.00
        SaleRecord::create([
            'import_id' => $import->id,
            'order_id' => 'ORD-2',
            'date' => '2024-01-11',
            'customer_id' => 'C2',
            'customer_name' => 'Customer B',
            'product_id' => 'P2',
            'product_name' => 'Laptop Pro',
            'category' => 'Electronics',
            'quantity' => 1,
            'unit_price' => 200.00,
            'discount' => 0.00,
            'total_amount' => 200.00,
            'country' => 'México',
        ]);

        // Record 3: Home & Kitchen in Colombia, total = 3 * 40 * (1 - 0.00) = 120.00
        SaleRecord::create([
            'import_id' => $import->id,
            'order_id' => 'ORD-3',
            'date' => '2024-01-12',
            'customer_id' => 'C3',
            'customer_name' => 'Customer C',
            'product_id' => 'P3',
            'product_name' => 'Coffee Maker',
            'category' => 'Home & Kitchen',
            'quantity' => 3,
            'unit_price' => 40.00,
            'discount' => 0.00,
            'total_amount' => 120.00,
            'country' => 'Colombia',
        ]);

        $response = $this->getJson("/api/reports/summary?import_id={$import->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total_revenue' => 500.00,
                    'total_units_sold' => 6,
                    'total_transactions' => 3,
                ],
            ]);

        $data = $response->json();

        // Check Top 5 Products: Laptop Pro ($200) should be #1, Phone X ($180) #2, Coffee Maker ($120) #3
        $this->assertCount(3, $data['top_products']);
        $this->assertEquals('P2', $data['top_products'][0]['product_id']);
        $this->assertEquals(200.00, $data['top_products'][0]['total_revenue']);
        $this->assertEquals('P1', $data['top_products'][1]['product_id']);
        $this->assertEquals(180.00, $data['top_products'][1]['total_revenue']);

        // Check Category Breakdown: Electronics ($380) and Home & Kitchen ($120)
        $this->assertCount(2, $data['category_distribution']);
        $this->assertEquals('Electronics', $data['category_distribution'][0]['category']);
        $this->assertEquals(380.00, $data['category_distribution'][0]['total_revenue']);

        // Check Country Breakdown: Colombia ($300) and México ($200)
        $this->assertCount(2, $data['geographical_distribution']);
        $this->assertEquals('Colombia', $data['geographical_distribution'][0]['country']);
        $this->assertEquals(300.00, $data['geographical_distribution'][0]['total_revenue']);
    }
}
