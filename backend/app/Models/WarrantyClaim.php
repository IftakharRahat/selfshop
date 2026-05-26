<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarrantyClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'claim_number',
        'order_id',
        'order_product_id',
        'product_id',
        'user_id',
        'vendor_id',
        'warranty_days',
        'delivered_at',
        'warranty_expires_at',
        'reason',
        'images',
        'status',
        'admin_note',
        'responded_at',
        'responded_by',
    ];

    protected $casts = [
        'images'              => 'array',
        'delivered_at'        => 'date',
        'warranty_expires_at' => 'date',
        'responded_at'        => 'datetime',
    ];

    /**
     * Auto-generate claim_number on creating.
     */
    protected static function booted(): void
    {
        static::creating(function (WarrantyClaim $claim) {
            if (!$claim->claim_number) {
                $prefix = 'WC-' . now()->format('Ym');
                $last = static::where('claim_number', 'like', $prefix . '%')
                    ->orderByDesc('id')
                    ->value('claim_number');

                $seq = $last ? ((int) substr($last, -5)) + 1 : 1;
                $claim->claim_number = $prefix . '-' . str_pad($seq, 5, '0', STR_PAD_LEFT);
            }
        });
    }

    // ── Relationships ──

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderProduct()
    {
        return $this->belongsTo(Orderproduct::class, 'order_product_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // ── Scopes ──

    /**
     * Only claims whose warranty hasn't expired yet.
     */
    public function scopeWithinWarranty($query)
    {
        return $query->where('warranty_expires_at', '>=', now()->toDateString());
    }

    /**
     * Filter by status.
     */
    public function scopeOfStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
