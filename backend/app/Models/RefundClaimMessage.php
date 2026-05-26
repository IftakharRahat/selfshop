<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RefundClaimMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'refund_claim_id',
        'sender_type',
        'user_id',
        'admin_id',
        'message',
        'attachment_path',
    ];

    public function claim()
    {
        return $this->belongsTo(RefundClaim::class, 'refund_claim_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
