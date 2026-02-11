<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorCategoryDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'discount_percent',
        'start_date',
        'end_date',
    ];

    protected $casts = [
        'discount_percent' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
