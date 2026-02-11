<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'company_name',
        'slug',
        'business_type',
        'logo_path',
        'banner_path',
        'contact_name',
        'contact_email',
        'contact_phone',
        'country',
        'state',
        'city',
        'postcode',
        'address_line_1',
        'address_line_2',
        'pickup_location_label',
        'status',
        'approved_at',
        'rejected_at',
        'suspended_at',
        'notes',
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function kycDocuments()
    {
        return $this->hasMany(VendorKycDocument::class);
    }

    public function warehouses()
    {
        return $this->hasMany(VendorWarehouse::class);
    }

    public function payoutAccounts()
    {
        return $this->hasMany(VendorPayoutAccount::class);
    }
}

