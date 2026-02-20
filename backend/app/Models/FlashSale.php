<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSale extends Model
{
    protected $fillable = ['title', 'start_time', 'end_time', 'status', 'banner_image', 'registration_deadline', 'vendor_registration'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'registration_deadline' => 'datetime',
        'vendor_registration' => 'boolean',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'flash_sale_products')
            ->withPivot('discount_percentage')
            ->withTimestamps();
    }

    public function flashSaleProducts()
    {
        return $this->hasMany(FlashSaleProduct::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'Active')
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now());
    }
}
