<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorWarehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'name',
        'label',
        'country',
        'state',
        'city',
        'postcode',
        'address_line_1',
        'address_line_2',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}

