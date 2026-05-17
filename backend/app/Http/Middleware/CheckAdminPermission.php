<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Middleware to enforce granular admin permissions.
 * Superadmins bypass all checks. Other admins must have
 * at least one of the specified direct permissions to access the route.
 *
 * Usage: ->middleware('admin.permission:banner.edit')
 *        ->middleware('admin.permission:supplier.view|supplier.all')  // OR logic
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

        // Support pipe-delimited OR permissions (e.g. 'supplier.view|supplier.all')
        $permissions = explode('|', $permission);
        foreach ($permissions as $perm) {
            if ($admin->hasDirectPermission(trim($perm))) {
                return $next($request);
            }
        }

        // Denied — redirect back with error
        return redirect()->back()->with('error', 'You do not have permission to perform this action.');
    }
}
