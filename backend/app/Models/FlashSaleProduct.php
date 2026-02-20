<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FlashSaleProduct extends Model
{
    protected $fillable = ['flash_sale_id', 'product_id', 'discount_percentage', 'vendor_id', 'campaign_price', 'seller_sku'];

    public function flashSale()
    {
        return $this->belongsTo(FlashSale::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}
