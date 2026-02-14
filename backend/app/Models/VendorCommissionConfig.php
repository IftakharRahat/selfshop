<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCommissionConfig extends Model
{
    protected $table = 'vendor_commission_config';

    protected $fillable = [
        'vendor_id',
        'category_id',
        'commission_percent',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
}
