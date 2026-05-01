<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketingCampaign extends Model
{
    protected $fillable = ['name', 'code', 'created_by', 'status'];

    /**
     * Admin who created this campaign.
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\Admin::class, 'created_by');
    }

    /**
     * Users who registered via this campaign link.
     */
    public function users()
    {
        return $this->hasMany(\App\Models\User::class, 'campaign_code', 'code');
    }

    /**
     * Get stats for this campaign.
     */
    public function getStatsAttribute(): array
    {
        $users = \App\Models\User::where('campaign_code', $this->code);

        return [
            'signups'       => (clone $users)->count(),
            'subscriptions' => (clone $users)->where('membership_status', 'Paid')->count(),
            'active'        => (clone $users)->where('status', 'Active')->count(),
        ];
    }
}
