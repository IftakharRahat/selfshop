<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VariantSize extends Model
{
    use HasFactory;

    protected $fillable = [
        'varient_id',
        'size_name',
        'price',
        'qty',
        'status',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'float',
    ];

    public function varient()
    {
        return $this->belongsTo(Varient::class, 'varient_id');
    }

    public function bulkPrices()
    {
        return $this->hasMany(VariantSizeBulkPrice::class);
    }
}
