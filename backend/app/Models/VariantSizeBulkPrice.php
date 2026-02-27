<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantSizeBulkPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'variant_size_id',
        'min_qty',
        'max_qty',
        'bulk_price',
    ];

    protected $casts = [
        'min_qty' => 'integer',
        'max_qty' => 'integer',
        'bulk_price' => 'decimal:2',
    ];

    public function variantSize()
    {
        return $this->belongsTo(VariantSize::class);
    }
}
