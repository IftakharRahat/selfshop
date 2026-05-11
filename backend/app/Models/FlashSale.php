<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

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
        // Flash sale times are created from admin datetime-local inputs (BD local time).
        // Use a fixed local timezone for active-window checks to avoid UTC server drift.
        $tz = env('FLASH_SALE_TIMEZONE', 'Asia/Dhaka');
        $nowLocal = Carbon::now($tz)->format('Y-m-d H:i:s');

        return $query->where('status', 'Active')
            ->where('start_time', '<=', $nowLocal)
            ->where('end_time', '>=', $nowLocal);
    }
}
