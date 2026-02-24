<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesTargetParticipant extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_target_id',
        'user_id',
        'joined_at',
        'completed_at',
        'reward_claimed_at',
        'achieved_value',
        'progress_percent',
        'claimed_reward_type',
        'claimed_reward_value',
        'claimed_reward_note',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'completed_at' => 'datetime',
        'reward_claimed_at' => 'datetime',
        'achieved_value' => 'float',
        'progress_percent' => 'float',
        'claimed_reward_value' => 'float',
    ];

    public function salesTarget()
    {
        return $this->belongsTo(SalesTarget::class, 'sales_target_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
