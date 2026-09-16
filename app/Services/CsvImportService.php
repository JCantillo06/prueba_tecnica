<?php

namespace App\Services;

use App\Models\Import;
use App\Models\ImportError;
use App\Models\SaleRecord;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvImportService
{
    private const CHUNK_SIZE = 1000;

    /**
     * Process the CSV file for a given Import record.
     */
    public function process(Import $import, ?string $filePath = null): void
    {
        $path = $filePath ?: Storage::disk('local')->path($import->file_name);

        if (!file_exists($path)) {
            $import->update([
                'status' => 'failed',
                'error_message' => "El archivo no existe en la ruta: {$path}",
                'completed_at' => now(),
            ]);
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $handle = fopen($path, 'r');
        if (!$handle) {
            $import->update([
                'status' => 'failed',
                'error_message' => 'No se pudo abrir el archivo CSV para lectura.',
                'completed_at' => now(),
            ]);
            return;
        }

        try {
            // Check and remove UTF-8 BOM if present
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Detect delimiter (comma, semicolon, tab, pipe)
            $firstLine = fgets($handle);
            rewind($handle);
            if ($bom === "\xEF\xBB\xBF") {
                fread($handle, 3);
            }

            $delimiter = $this->detectDelimiter($firstLine);

            // Read raw header line
            $rawHeaderLine = fgets($handle);
            if (!$rawHeaderLine) {
                throw new Exception('El archivo CSV está vacío.');
            }
            if (!mb_check_encoding($rawHeaderLine, 'UTF-8')) {
                $rawHeaderLine = mb_convert_encoding($rawHeaderLine, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
            }

            $header = $this->parseCsvLine($rawHeaderLine, $delimiter);
            if (!$header || empty(array_filter($header))) {
                throw new Exception('El archivo CSV está vacío o no contiene una cabecera válida.');
            }

            // Clean header column names
            $headerMap = $this->mapHeaders($header);
            $this->validateRequiredHeaders($headerMap);

            $rowNumber = 1; // Row 1 is header
            $totalRows = 0;
            $successRows = 0;
            $failedRows = 0;
            $totalRevenue = 0.0;

            $validBatch = [];
            $errorBatch = [];
            $now = now()->toDateTimeString();

            while (($rawLine = fgets($handle)) !== false) {
                $rowNumber++;

                if (trim($rawLine) === '') {
                    continue;
                }

                if (!mb_check_encoding($rawLine, 'UTF-8')) {
                    $rawLine = mb_convert_encoding($rawLine, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
                }

                $row = $this->parseCsvLine($rawLine, $delimiter);

                // Skip completely empty lines
                if ($this->isEmptyRow($row)) {
                    continue;
                }

                $totalRows++;

                // Validate row
                $validation = $this->validateRow($row, $headerMap, $rowNumber);

                if ($validation['is_valid']) {
                    $record = $validation['data'];
                    $record['import_id'] = $import->id;
                    $record['created_at'] = $now;
                    $record['updated_at'] = $now;

                    $validBatch[] = $record;
                    $totalRevenue += (float) $record['total_amount'];
                    $successRows++;
                } else {
                    $rawPreview = [];
                    foreach ($headerMap as $hKey => $hIdx) {
                        $rawPreview[$hKey] = $row[$hIdx] ?? null;
                    }

                    $errorBatch[] = [
                        'import_id' => $import->id,
                        'row_number' => $rowNumber,
                        'raw_data' => json_encode($rawPreview, JSON_UNESCAPED_UNICODE),
                        'error_reason' => $validation['error_reason'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $failedRows++;
                }

                // Process chunks
                if (count($validBatch) >= self::CHUNK_SIZE) {
                    $this->flushValidBatch($validBatch);
                    $validBatch = [];
                }

                if (count($errorBatch) >= self::CHUNK_SIZE) {
                    $this->flushErrorBatch($errorBatch);
                    $errorBatch = [];
                }
            }

            // Flush remaining batches
            if (!empty($validBatch)) {
                $this->flushValidBatch($validBatch);
            }
            if (!empty($errorBatch)) {
                $this->flushErrorBatch($errorBatch);
            }

            fclose($handle);

            // Update Import statistics
            $import->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'successful_rows' => $successRows,
                'failed_rows' => $failedRows,
                'total_revenue' => round($totalRevenue, 2),
                'completed_at' => now(),
            ]);

        } catch (Exception $e) {
            if (is_resource($handle)) {
                fclose($handle);
            }

            Log::error("Error procesando importación ID {$import->id}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            $import->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
        }
    }

    /**
     * Detect CSV delimiter accurately.
     */
    private function detectDelimiter(string $sampleLine): string
    {
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;

        foreach ($delimiters as $delim) {
            $count = substr_count($sampleLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        return $bestDelimiter;
    }

    /**
     * Parse a CSV line with quote and format resilience.
     */
    private function parseCsvLine(string $line, string $delimiter): array
    {
        $trimmed = trim($line, "\r\n");
        if ($trimmed === '') {
            return [];
        }

        // If entire line is wrapped in enclosing quotes: "order_id,date,..."
        if (str_starts_with($trimmed, '"') && str_ends_with($trimmed, '"')) {
            $inner = substr($trimmed, 1, -1);
            if (str_contains($inner, $delimiter)) {
                $trimmed = str_replace('""', '"', $inner);
            }
        }

        $parsed = str_getcsv($trimmed, $delimiter);
        if (!is_array($parsed)) {
            return [];
        }

        return array_map(function ($val) {
            if (is_string($val) && !mb_check_encoding($val, 'UTF-8')) {
                return mb_convert_encoding($val, 'UTF-8', 'ISO-8859-1, Windows-1252, UTF-8');
            }
            return $val;
        }, $parsed);
    }

    /**
     * Map header names to normalized keys.
     */
    private function mapHeaders(array $header): array
    {
        $map = [];
        foreach ($header as $index => $col) {
            $cleaned = strtolower(trim((string)$col));
            $cleaned = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $cleaned);
            $cleaned = str_replace([' ', '-', '"', "'"], ['_', '_', '', ''], $cleaned);
            if (!empty($cleaned)) {
                $map[$cleaned] = $index;
            }
        }
        return $map;
    }

    /**
     * Verify all required CSV columns are present.
     */
    private function validateRequiredHeaders(array $headerMap): void
    {
        $required = [
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

        $missing = [];
        foreach ($required as $field) {
            if (!array_key_exists($field, $headerMap)) {
                $missing[] = $field;
            }
        }

        if (!empty($missing)) {
            throw new Exception('El archivo CSV no contiene las columnas obligatorias requeridas: ' . implode(', ', $missing));
        }
    }

    /**
     * Check if a row has only null or empty string values.
     */
    private function isEmptyRow(array $row): bool
    {
        if (empty($row)) {
            return true;
        }
        foreach ($row as $val) {
            if ($val !== null && trim((string)$val) !== '') {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate an individual row and compute total amount.
     */
    private function validateRow(array $row, array $headerMap, int $rowNumber): array
    {
        $errors = [];

        // Helper to get column value
        $getValue = function (string $key) use ($row, $headerMap) {
            $index = $headerMap[$key] ?? null;
            if ($index === null || !isset($row[$index])) {
                return null;
            }
            $val = trim((string)$row[$index]);
            return $val === '' ? null : $val;
        };

        $orderId = $getValue('order_id');
        $dateStr = $getValue('date');
        $customerId = $getValue('customer_id');
        $customerName = $getValue('customer_name') ?? 'Cliente Desconocido';
        $productId = $getValue('product_id');
        $productName = $getValue('product_name');
        $category = $getValue('category');
        $quantityRaw = $getValue('quantity');
        $unitPriceRaw = $getValue('unit_price');
        $discountRaw = $getValue('discount');
        $country = $getValue('country');

        // Required text fields
        if (empty($orderId)) {
            $errors[] = 'El campo order_id es obligatorio y no puede estar vacío.';
        }
        if (empty($customerId)) {
            $errors[] = 'El campo customer_id es obligatorio y no puede estar vacío.';
        }
        if (empty($productId)) {
            $errors[] = 'El campo product_id es obligatorio y no puede estar vacío.';
        }
        if (empty($productName)) {
            $errors[] = 'El campo product_name es obligatorio y no puede estar vacío.';
        }
        if (empty($category)) {
            $errors[] = 'El campo category es obligatorio y no puede estar vacío.';
        }
        if (empty($country)) {
            $errors[] = 'El campo country es obligatorio y no puede estar vacío.';
        }

        // Date validation (Strict ISO YYYY-MM-DD format e.g. 2024-12-31)
        $parsedDate = null;
        if (empty($dateStr)) {
            $errors[] = 'El campo date es obligatorio y no puede estar vacío.';
        } else {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $errors[] = "Formato de fecha inválido ('{$dateStr}'). Debe tener formato estricto YYYY-MM-DD (e.g. 2024-12-31).";
            } else {
                try {
                    $d = Carbon::createFromFormat('Y-m-d', $dateStr);
                    if (!$d || $d->format('Y-m-d') !== $dateStr) {
                        $errors[] = "La fecha ('{$dateStr}') no corresponde a un día de calendario válido.";
                    } else {
                        $parsedDate = $d->format('Y-m-d');
                    }
                } catch (Exception $e) {
                    $errors[] = "Formato de fecha inválido ('{$dateStr}'). Debe ser una fecha válida YYYY-MM-DD.";
                }
            }
        }

        // Quantity validation (> 0)
        $quantity = null;
        if ($quantityRaw === null) {
            $errors[] = 'El campo quantity es obligatorio.';
        } elseif (!is_numeric($quantityRaw)) {
            $errors[] = "El campo quantity ('{$quantityRaw}') debe ser un valor numérico.";
        } else {
            $quantity = (int) $quantityRaw;
            if ($quantity <= 0) {
                $errors[] = "La cantidad debe ser mayor a cero (valor recibido: {$quantityRaw}).";
            }
        }

        // Unit price validation (>= 0)
        $unitPrice = null;
        if ($unitPriceRaw === null) {
            $errors[] = 'El campo unit_price es obligatorio.';
        } elseif (!is_numeric($unitPriceRaw)) {
            $errors[] = "El campo unit_price ('{$unitPriceRaw}') debe ser un valor numérico.";
        } else {
            $unitPrice = (float) $unitPriceRaw;
            if ($unitPrice < 0) {
                $errors[] = "El precio unitario no puede ser negativo (valor recibido: {$unitPriceRaw}).";
            }
        }

        // Discount validation (0 <= discount <= 1)
        $discount = 0.0;
        if ($discountRaw !== null) {
            if (!is_numeric($discountRaw)) {
                $errors[] = "El campo discount ('{$discountRaw}') debe ser numérico.";
            } else {
                $discount = (float) $discountRaw;
                if ($discount < 0 || $discount > 1) {
                    $errors[] = "El descuento debe estar en el rango de 0 a 1 (valor recibido: {$discountRaw}).";
                }
            }
        }

        if (!empty($errors)) {
            return [
                'is_valid' => false,
                'error_reason' => implode(' | ', $errors),
            ];
        }

        // Business formula: quantity * unit_price * (1 - discount)
        $totalAmount = round($quantity * $unitPrice * (1 - $discount), 2);

        return [
            'is_valid' => true,
            'data' => [
                'order_id' => $orderId,
                'date' => $parsedDate,
                'customer_id' => $customerId,
                'customer_name' => $customerName,
                'product_id' => $productId,
                'product_name' => $productName,
                'category' => $category,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'country' => $country,
            ],
        ];
    }

    /**
     * Bulk insert valid records in chunk.
     */
    private function flushValidBatch(array $records): void
    {
        DB::transaction(function () use ($records) {
            SaleRecord::insert($records);
        });
    }

    /**
     * Bulk insert error records in chunk.
     */
    private function flushErrorBatch(array $errors): void
    {
        DB::transaction(function () use ($errors) {
            ImportError::insert($errors);
        });
    }
}
