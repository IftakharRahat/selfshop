<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class ResellerActivation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return redirect('/login');
        }

        if (
            $user->status === 'Active' &&
            (empty($user->expire_date) || $user->expire_date >= date('Y-m-d'))
        ) {
            return $next($request);
        }

        // Expired memberships must renew package.
        if (!empty($user->expire_date) && $user->expire_date < date('Y-m-d')) {
            $user->status = 'Inactive';
            $user->membership_status = 'Unpaid';
            $user->save();
        }

        return redirect('/our-packages');
    }
}
