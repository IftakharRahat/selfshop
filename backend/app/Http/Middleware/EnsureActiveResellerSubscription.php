<?php

namespace App\Http\Middleware;

use App\Services\ResellerSubscriptionService;
use Closure;
use Illuminate\Http\Request;

class EnsureActiveResellerSubscription
{
    public function handle(Request $request, Closure $next)
    {
        $subscription = ResellerSubscriptionService::state($request->user());

        if ($subscription['is_active']) {
            return $next($request);
        }

        return response()->json([
            'status' => false,
            'message' => 'An active subscription is required to access this feature.',
            'code' => 'SUBSCRIPTION_REQUIRED',
            'subscription_required' => true,
            'subscription' => $subscription,
            'latest_invoice' => $subscription['latest_invoice'],
        ], 403);
    }
}
