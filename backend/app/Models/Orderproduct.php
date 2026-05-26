<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;


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
        'selling_price',
        'warranty_days_snapshot',
    ];

    protected $casts = [
        'shipped_at' => 'datetime',
        'warranty_days_snapshot' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function (Orderproduct $orderproduct) {
            if (!Schema::hasColumn('orderproducts', 'warranty_days_snapshot')) {
                return;
            }

            if (!empty($orderproduct->warranty_days_snapshot) || empty($orderproduct->product_id)) {
                return;
            }

            $days = Product::where('id', $orderproduct->product_id)->value('warranty_days');
            $orderproduct->warranty_days_snapshot = $days > 0 ? (int) $days : null;
        });
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
