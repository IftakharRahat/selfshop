<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class Orderproduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'productCode',
        'productName',
        'productPrice',
        'quantity',
        'tracking_number',
        'shipped_at',
        'fulfillment_status',
        'fulfillment_type',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}