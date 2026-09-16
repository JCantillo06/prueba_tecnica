<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sale_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained('imports')->onDelete('cascade');
            $table->string('order_id', 100)->index();
            $table->date('date')->index();
            $table->string('customer_id', 100)->index();
            $table->string('customer_name', 255)->nullable();
            $table->string('product_id', 100)->index();
            $table->string('product_name', 255);
            $table->string('category', 100)->index();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount', 6, 4)->default(0.0000);
            $table->decimal('total_amount', 14, 2);
            $table->string('country', 100)->index();
            $table->timestamps();

            // Aggregation performance indexes
            $table->index(['import_id', 'category']);
            $table->index(['import_id', 'country']);
            $table->index(['import_id', 'product_id', 'product_name']);
            $table->index(['import_id', 'total_amount']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_records');
    }
};
