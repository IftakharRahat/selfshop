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
        'approval_type',
        'is_verified_badge',
        'verified_badge_at',
        'verified_badge_by',
        'approved_at',
        'rejected_at',
        'suspended_at',
        'notes',
    ];

    protected $casts = [
        'is_verified_badge' => 'boolean',
        'verified_badge_at' => 'datetime',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'suspended_at' => 'datetime',
    ];

    /**
     * Generate the masked supplier ID for privately approved vendors.
     */
    public function getPrivateIdAttribute(): string
    {
        return 'SID-' . str_pad((string) $this->id, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Return company_name for public vendors, masked ID for private vendors.
     */
    public function getPublicNameAttribute(): string
    {
        if ($this->approval_type === 'private') {
            return $this->private_id;
        }

        return $this->company_name ?? '';
    }

    /**
     * Return slug for public vendors, masked slug for private vendors.
     */
    public function getPublicSlugAttribute(): string
    {
        if ($this->approval_type === 'private') {
            return strtolower($this->private_id);
        }

        return $this->slug ?? '';
    }

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

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function shippingMethods()
    {
        return $this->hasMany(VendorShippingMethod::class);
    }

    public function earnings()
    {
        return $this->hasMany(VendorEarning::class);
    }

    public function payoutRequests()
    {
        return $this->hasMany(VendorPayoutRequest::class);
    }

    public function payouts()
    {
        return $this->hasMany(VendorPayout::class);
    }

    public function followers()
    {
        return $this->hasMany(VendorFollower::class);
    }
}
