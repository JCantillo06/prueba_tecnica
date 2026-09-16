<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_id',
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
        'total_amount',
        'country',
    ];

    protected $casts = [
        'date' => 'date:Y-m-d',
        'quantity' => 'integer',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:4',
        'total_amount' => 'decimal:2',
    ];

    public function import(): BelongsTo
    {
        return $this->belongsTo(Import::class, 'import_id');
    }
}
