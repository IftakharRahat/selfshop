<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPayoutAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'paymenttype_id',
        'channel_type',
        'provider_name',
        'account_name',
        'account_number',
        'bank_name',
        'branch_name',
        'routing_number',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function paymenttype()
    {
        return $this->belongsTo(Paymenttype::class, 'paymenttype_id');
    }
}
