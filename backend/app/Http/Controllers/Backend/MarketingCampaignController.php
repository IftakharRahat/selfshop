<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\MarketingCampaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketingCampaignController extends Controller
{
    /**
     * Display all campaigns with stats.
     */
    public function index()
    {
        $campaigns = MarketingCampaign::orderBy('created_at', 'desc')->get();

        // Build stats in bulk to avoid N+1 queries in blade accessor.
        $codes = $campaigns->pluck('code')->filter()->values();
        $statsByCode = collect();
        if ($codes->isNotEmpty()) {
            $statsRows = User::query()
                ->select(
                    'campaign_code',
                    DB::raw('COUNT(*) as signups'),
                    DB::raw("SUM(CASE WHEN membership_status = 'Paid' THEN 1 ELSE 0 END) as subscriptions"),
                    DB::raw("SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active")
                )
                ->whereIn('campaign_code', $codes)
                ->groupBy('campaign_code')
                ->get();

            $statsByCode = $statsRows->keyBy('campaign_code');
        }

        // Build frontend base URL for generating links
        $frontendUrl = rtrim(
            env('FRONTEND_URL')
                ?: config('app.frontend_url')
                ?: env('CLIENT_URL')
                ?: 'http://localhost:3000',
            '/'
        );

        return view('backend.content.marketing.campaigns', compact('campaigns', 'frontendUrl', 'statsByCode'));
    }

    /**
     * Create a new campaign.
     */
    public function store(Request $request)
    {
        $name = trim($request->name ?? '');
        $code = trim($request->code ?? '');

        if ($name === '' && $code === '') {
            return redirect()->back()->with('error', 'Please fill at least one field.');
        }

        // Auto-fill missing values
        if ($name === '') {
            $name = $code;
        }
        if ($code === '') {
            $code = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));
            $code = trim($code, '-');
        } else {
            $code = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $code));
            $code = trim($code, '-');
        }

        // Ensure uniqueness
        $originalCode = $code;
        $counter = 1;
        while (MarketingCampaign::where('code', $code)->exists()) {
            $code = $originalCode . '-' . $counter;
            $counter++;
        }

        MarketingCampaign::create([
            'name'       => $name,
            'code'       => $code,
            'created_by' => Auth::guard('admin')->id(),
            'status'     => 'active',
        ]);

        return redirect()->back()->with('success', 'Campaign created successfully!');
    }

    /**
     * Delete a campaign.
     */
    public function destroy($id)
    {
        $campaign = MarketingCampaign::findOrFail($id);
        $campaign->delete();

        return redirect()->back()->with('success', 'Campaign deleted successfully!');
    }
}
