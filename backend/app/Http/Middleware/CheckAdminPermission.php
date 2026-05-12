<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to enforce granular admin permissions.
 * Superadmins bypass all checks. Other admins must have
 * the specified direct permission to access the route.
 *
 * Usage: ->middleware('admin.permission:banner.edit')
 */
class CheckAdminPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $admin = Auth::guard('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.loginview');
        }

        // Superadmin bypasses all permission checks
        if ($admin->isFullAdmin()) {
            return $next($request);
        }

        // Check direct permission only (not role-inherited)
        if ($admin->hasDirectPermission($permission)) {
            return $next($request);
        }

        // Denied — redirect back with error
        return redirect()->back()->with('error', 'You do not have permission to perform this action.');
    }
}
