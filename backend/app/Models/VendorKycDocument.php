<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorKycDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'document_type',
        'document_number',
        'document_path',
        'status',
        'verified_at',
        'verified_by',
        'review_notes',
    ];

    protected $casts = [
        'verified_at' => 'datetime',
    ];

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }
}

