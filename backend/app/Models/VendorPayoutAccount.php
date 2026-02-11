<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'channel_type',
        'provider_name',
        'account_name',
        'account_number',
        'routing_number',
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

