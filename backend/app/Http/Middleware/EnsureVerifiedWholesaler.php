<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureVerifiedWholesaler
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
        }
        if (!Auth::user()->is_verified_wholesaler) {
            return response()->json(['status' => false, 'message' => 'Vendor access requires verified wholesaler account'], 403);
        }
        return $next($request);
    }
}
