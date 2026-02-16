<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User;
use App\Services\VendorAdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorController extends Controller
{
    public function __construct(
        protected VendorAdminNotificationService $vendorNotificationService
    ) {}

    /**
     * List vendors for admin (Blade view). JSON via ?format=json.
     * GET /admin/vendors (auth.admin)
     */
    public function index(Request $request)
    {
        $vendors = Vendor::with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'status' => true,
                'data' => $vendors,
            ]);
        }

        return view('backend.content.vendors.index', compact('vendors'));
    }

    /**
     * Show full vendor details for admin.
     * GET /admin/vendors/{vendor}
     */
    public function show(Vendor $vendor)
    {
        $vendor->load(['user', 'kycDocuments', 'warehouses', 'payoutAccounts']);

        return view('backend.content.vendors.show', compact('vendor'));
    }

    /**
     * Approve a vendor and activate its user account.
     * POST /admin/vendors/{vendor}/approve
     */
    public function approve(Vendor $vendor)
    {
        $vendor->status = 'approved';
        $vendor->approved_at = now();
        $vendor->rejected_at = null;
        $vendor->save();

        $user = User::find($vendor->user_id);
        if ($user) {
            $user->status = 'Active';
            $user->is_verified_wholesaler = true;
            $user->save();
        }

        $this->vendorNotificationService->notifyVendor(
            $vendor,
            'Vendor account approved',
            'Your vendor account has been approved by admin. You can now manage your products and orders.',
            'success',
            ['event' => 'vendor_account_approved', 'vendor_id' => $vendor->id],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Vendor approved']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Vendor approved.');
    }

    /**
     * Reject a vendor application.
     * POST /admin/vendors/{vendor}/reject
     */
    public function reject(Request $request, Vendor $vendor)
    {
        $vendor->status = 'rejected';
        $vendor->rejected_at = now();
        $vendor->is_verified_badge = false;
        $vendor->verified_badge_at = null;
        $vendor->verified_badge_by = null;
        $vendor->notes = $request->input('reason');
        $vendor->save();

        $user = User::find($vendor->user_id);
        if ($user) {
            $user->is_verified_wholesaler = false;
            $user->save();
        }

        $this->vendorNotificationService->notifyVendor(
            $vendor,
            'Vendor account rejected',
            'Your vendor account was rejected by admin.' . ($vendor->notes ? ' Reason: ' . $vendor->notes : ''),
            'warning',
            ['event' => 'vendor_account_rejected', 'vendor_id' => $vendor->id, 'reason' => $vendor->notes],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Vendor rejected']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Vendor rejected.');
    }

    /**
     * Grant verified badge to an approved vendor.
     * POST /admin/vendors/{vendor}/verify-badge
     */
    public function verifyBadge(Vendor $vendor)
    {
        if ($vendor->status !== 'approved') {
            $message = 'Only approved vendors can receive a verified badge.';
            if (request()->wantsJson()) {
                return response()->json(['status' => false, 'message' => $message], 422);
            }
            return redirect()->back()->with('message', $message);
        }

        $vendor->is_verified_badge = true;
        $vendor->verified_badge_at = now();
        $vendor->verified_badge_by = Auth::guard('admin')->id();
        $vendor->save();

        $this->vendorNotificationService->notifyVendor(
            $vendor,
            'Verified badge granted',
            'Admin granted your vendor account a verified badge.',
            'success',
            ['event' => 'vendor_badge_granted', 'vendor_id' => $vendor->id],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Verified badge granted']);
        }
        return redirect()->back()->with('message', 'Verified badge granted.');
    }

    /**
     * Remove verified badge from a vendor.
     * POST /admin/vendors/{vendor}/remove-verified-badge
     */
    public function removeVerifiedBadge(Vendor $vendor)
    {
        $vendor->is_verified_badge = false;
        $vendor->verified_badge_at = null;
        $vendor->verified_badge_by = null;
        $vendor->save();

        $this->vendorNotificationService->notifyVendor(
            $vendor,
            'Verified badge removed',
            'Admin removed the verified badge from your vendor account.',
            'warning',
            ['event' => 'vendor_badge_removed', 'vendor_id' => $vendor->id],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Verified badge removed']);
        }
        return redirect()->back()->with('message', 'Verified badge removed.');
    }
}

