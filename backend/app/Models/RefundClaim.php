<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundClaim extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending',
        'in_progress',
        'approved',
        'rejected',
        'closed',
    ];

    protected $fillable = [
        'claim_number',
        'user_id',
        'order_id',
        'orderproduct_id',
        'product_id',
        'status',
        'delivery_date',
        'expires_at',
        'warranty_days',
        'message',
        'image_path',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'expires_at' => 'datetime',
        'warranty_days' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderproduct()
    {
        return $this->belongsTo(Orderproduct::class, 'orderproduct_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function messages()
    {
        return $this->hasMany(RefundClaimMessage::class)->orderBy('created_at');
    }
}
