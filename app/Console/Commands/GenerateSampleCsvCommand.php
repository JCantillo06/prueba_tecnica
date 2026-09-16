<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSampleCsvCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sales:generate-csv 
                            {--rows=25000 : Total number of rows to generate} 
                            {--errors=200 : Number of invalid/corrupt rows to introduce} 
                            {--output=storage/app/sample_sales.csv : Destination output path}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a synthetic retail sales CSV file for ETL stress testing';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $rows = (int) $this->option('rows');
        $errorsCount = (int) $this->option('errors');
        $outputPath = base_path($this->option('output'));

        $this->info("Generando archivo CSV con {$rows} registros ({$errorsCount} inconsistencias controladas)...");

        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            $this->error("No se pudo crear el archivo en: {$outputPath}");
            return Command::FAILURE;
        }

        // Header matching specification
        $header = [
            'order_id',
            'date',
            'customer_id',
            'customer_name',
            'product_id',
            'product_name',
            'category',
            'quantity',
            'unit_price',
            'discount',
            'country',
        ];
        fputcsv($handle, $header);

        $categories = [
            'Electronics' => [
                ['P001', 'Smartphone Pro Max', 799.99],
                ['P002', 'Wireless Noise Canceling Headphones', 199.99],
                ['P003', '4K Smart LED TV 55"', 549.00],
                ['P004', 'Ultrabook Laptop 16GB', 1199.50],
                ['P005', 'Smartwatch Fitness Tracker', 89.90],
                ['P006', 'Bluetooth Portable Speaker', 45.00],
            ],
            'Home & Kitchen' => [
                ['P007', 'Automatic Espresso Machine', 349.99],
                ['P008', 'Air Fryer XL 5.5L', 119.00],
                ['P009', 'Robot Vacuum Cleaner', 279.50],
                ['P010', 'Stainless Steel Cookware Set', 159.00],
            ],
            'Clothing & Fashion' => [
                ['P011', 'Premium Cotton Hoodie', 49.99],
                ['P012', 'Classic Denim Jeans', 59.90],
                ['P013', 'Running Performance Shoes', 89.95],
                ['P014', 'Waterproof Winter Jacket', 129.00],
            ],
            'Beauty & Personal Care' => [
                ['P015', 'Hydrating Skin Serum', 29.50],
                ['P016', 'Ionic Hair Dryer 2200W', 65.00],
                ['P017', 'Electric Sonic Toothbrush', 42.00],
            ],
            'Sports & Outdoors' => [
                ['P018', 'Adjustable Dumbbell Set', 189.00],
                ['P019', 'Mountain Bike Helmet', 55.00],
                ['P020', 'Camping Waterproof Tent 4P', 145.00],
            ],
        ];

        $countries = [
            'Colombia', 'México', 'Argentina', 'Chile', 'Perú',
            'España', 'Estados Unidos', 'Brasil', 'Ecuador', 'Panamá'
        ];

        $customers = [
            ['C001', 'Empresa Alfa S.A.S.'],
            ['C002', 'Distribuidora Global S.A.'],
            ['C003', 'Comercializadora del Norte'],
            ['C004', 'Inversiones Pacífico'],
            ['C005', 'Retail Express Ltda.'],
            ['C006', 'Corporación Horizonte'],
            ['C007', 'Soluciones Comerciales Andes'],
            ['C008', 'Almacenes La Cúspide'],
            ['C009', 'Importaciones y Exportaciones Delta'],
            ['C010', 'MegaStore del Sur'],
        ];

        // Determine error rows indices
        $errorIndices = [];
        if ($errorsCount > 0 && $rows > 0) {
            $errorIndices = array_flip(array_rand(range(1, $rows), min($errorsCount, $rows)));
        }

        $bar = $this->output->createProgressBar($rows);
        $bar->start();

        $startDate = strtotime('2024-01-01');
        $endDate = strtotime('2024-12-31');

        for ($i = 1; $i <= $rows; $i++) {
            $catKeys = array_keys($categories);
            $cat = $catKeys[array_rand($catKeys)];
            $prodList = $categories[$cat];
            $prod = $prodList[array_rand($prodList)];
            $cust = $customers[array_rand($customers)];
            $country = $countries[array_rand($countries)];

            $randomTime = mt_rand($startDate, $endDate);
            $date = date('Y-m-d', $randomTime);
            $orderId = 'ORD-' . str_pad((string)$i, 7, '0', STR_PAD_LEFT);
            $quantity = mt_rand(1, 15);
            $unitPrice = $prod[2];
            $discount = round(mt_rand(0, 25) / 100, 2); // 0.00 to 0.25

            // Introduce deliberate controlled errors
            if (isset($errorIndices[$i])) {
                $errorType = $i % 5;
                switch ($errorType) {
                    case 0:
                        $date = '31-02-2024'; // Invalid date
                        break;
                    case 1:
                        $unitPrice = -50.00; // Negative price
                        break;
                    case 2:
                        $quantity = 0; // Zero quantity
                        break;
                    case 3:
                        $orderId = ''; // Empty required field
                        break;
                    case 4:
                        $discount = 1.50; // Discount > 100%
                        break;
                }
            }

            fputcsv($handle, [
                $orderId,
                $date,
                $cust[0],
                $cust[1],
                $prod[0],
                $prod[1],
                $cat,
                $quantity,
                $unitPrice,
                $discount,
                $country,
            ]);

            if ($i % 500 === 0) {
                $bar->advance(500);
            }
        }

        $bar->finish();
        fclose($handle);

        $this->newLine();
        $this->info("¡Archivo CSV generado exitosamente en: {$outputPath}!");

        return Command::SUCCESS;
    }
}
