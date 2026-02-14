<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorPayout extends Model
{
    protected $fillable = [
        'vendor_id',
        'payout_request_id',
        'amount',
        'status',
        'reference',
        'paid_at',
        'admin_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(VendorPayoutRequest::class);
    }

    public function earnings(): HasMany
    {
        return $this->hasMany(VendorEarning::class, 'payout_id');
    }
}
