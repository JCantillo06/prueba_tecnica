<?php

namespace Database\Factories;

use App\Models\Import;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImportFactory extends Factory
{
    protected $model = Import::class;

    public function definition(): array
    {
        return [
            'file_name' => 'imports/' . $this->faker->uuid() . '.csv',
            'original_name' => $this->faker->word() . '.csv',
            'status' => 'completed',
            'total_rows' => 100,
            'successful_rows' => 95,
            'failed_rows' => 5,
            'total_revenue' => $this->faker->randomFloat(2, 1000, 50000),
            'started_at' => now()->subMinutes(2),
            'completed_at' => now(),
        ];
    }
}
