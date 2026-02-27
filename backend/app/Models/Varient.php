<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Varient extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'title',
        'qty',
        'price',
        'status',
        'extra_delivery_charge',
        'color_name',
        'color_code',
        'image',
    ];

    protected $casts = [
        'qty' => 'integer',
        'price' => 'float',
        'extra_delivery_charge' => 'float',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sizes()
    {
        return $this->hasMany(VariantSize::class, 'varient_id');
    }
}
