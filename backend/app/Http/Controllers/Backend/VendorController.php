<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User;
use App\Services\VendorAdminNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

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
        $status = strtolower((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $vendorsQuery = Vendor::with('user')
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('company_name', 'like', '%' . $search . '%')
                        ->orWhere('contact_name', 'like', '%' . $search . '%')
                        ->orWhere('contact_email', 'like', '%' . $search . '%')
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('email', 'like', '%' . $search . '%');
                        });
                });
            })
            ->orderByDesc('created_at');

        $vendors = $vendorsQuery->paginate(30)->withQueryString();

        $summary = [
            'all' => Vendor::count(),
            'approved' => Vendor::where('status', 'approved')->count(),
            'pending' => Vendor::where('status', 'pending')->count(),
            'rejected' => Vendor::where('status', 'rejected')->count(),
            'suspended' => Vendor::where('status', 'suspended')->count(),
        ];

        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'status' => true,
                'data' => $vendors,
            ]);
        }

        return view('backend.content.vendors.index', compact('vendors', 'summary', 'status', 'search'));
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
     * Edit supplier account details.
     * GET /admin/vendors/{vendor}/edit
     */
    public function edit(Vendor $vendor)
    {
        $vendor->load('user');

        return view('backend.content.vendors.edit', compact('vendor'));
    }

    /**
     * Update supplier account details.
     * PUT /admin/vendors/{vendor}
     */
    public function update(Request $request, Vendor $vendor)
    {
        $validated = $request->validate([
            'company_name' => ['required', 'string', 'max:255'],
            'business_type' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:32'],
            'country' => ['nullable', 'string', 'max:100'],
            'city' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'suspended'])],
            'approval_type' => ['nullable', Rule::in(['public', 'private'])],
            'is_verified_badge' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'user_status' => ['nullable', Rule::in(['Active', 'Inactive', 'Block'])],
            'membership_status' => ['nullable', Rule::in(['Paid', 'Unpaid'])],
        ]);

        $vendor->company_name = $validated['company_name'];
        $vendor->business_type = $validated['business_type'] ?? null;
        $vendor->contact_name = $validated['contact_name'] ?? null;
        $vendor->contact_email = $validated['contact_email'] ?? null;
        $vendor->contact_phone = $validated['contact_phone'] ?? null;
        $vendor->country = $validated['country'] ?? null;
        $vendor->city = $validated['city'] ?? null;
        $vendor->address_line_1 = $validated['address_line_1'] ?? null;
        $vendor->status = $validated['status'];
        if ($validated['status'] === 'approved' && isset($validated['approval_type'])) {
            $vendor->approval_type = $validated['approval_type'];
        }
        $vendor->is_verified_badge = $request->boolean('is_verified_badge');
        $vendor->notes = $validated['notes'] ?? null;

        if ($validated['status'] === 'approved') {
            $vendor->approved_at = $vendor->approved_at ?? now();
            $vendor->rejected_at = null;
        } elseif ($validated['status'] === 'rejected') {
            $vendor->rejected_at = now();
            $vendor->approved_at = null;
            $vendor->is_verified_badge = false;
            $vendor->verified_badge_at = null;
            $vendor->verified_badge_by = null;
        } elseif ($validated['status'] === 'pending') {
            $vendor->approved_at = null;
            $vendor->rejected_at = null;
            $vendor->is_verified_badge = false;
            $vendor->verified_badge_at = null;
            $vendor->verified_badge_by = null;
        } else {
            $vendor->is_verified_badge = false;
            $vendor->verified_badge_at = null;
            $vendor->verified_badge_by = null;
        }

        if ($vendor->is_verified_badge) {
            $vendor->verified_badge_at = $vendor->verified_badge_at ?? now();
            $vendor->verified_badge_by = $vendor->verified_badge_by ?? Auth::guard('admin')->id();
        } else {
            $vendor->verified_badge_at = null;
            $vendor->verified_badge_by = null;
        }

        $vendor->save();

        $user = $vendor->user;
        if ($user) {
            if (isset($validated['user_status'])) {
                $user->status = $validated['user_status'];
            }
            if (isset($validated['membership_status'])) {
                $user->membership_status = $validated['membership_status'];
            }

            $user->is_verified_wholesaler = $vendor->status === 'approved';
            $user->save();
        }

        return redirect()->route('admin.vendors.index')->with('message', 'Supplier account updated.');
    }

    /**
     * Approve a vendor and activate its user account.
     * POST /admin/vendors/{vendor}/approve
     */
    public function approve(Request $request, Vendor $vendor)
    {
        $vendor->status = 'approved';
        $vendor->approval_type = $request->input('approval_type', 'private');
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
            'Supplier account approved',
            'Your supplier account has been approved by SelfShop. You can now manage your products and orders.',
            'success',
            ['event' => 'vendor_account_approved', 'vendor_id' => $vendor->id],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Supplier approved']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Supplier approved.');
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
            'Supplier account rejected',
            'Your supplier account application has been reviewed by SelfShop and could not be approved at this time.' . ($vendor->notes ? ' Reason: ' . $vendor->notes : ''),
            'warning',
            ['event' => 'vendor_account_rejected', 'vendor_id' => $vendor->id, 'reason' => $vendor->notes],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Supplier rejected']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Supplier rejected.');
    }

    /**
     * Grant verified badge to an approved vendor.
     * POST /admin/vendors/{vendor}/verify-badge
     */
    public function verifyBadge(Vendor $vendor)
    {
        if ($vendor->status !== 'approved') {
            $message = 'Only approved suppliers can receive a verified badge.';
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
            'Congratulations! Your supplier account has been awarded a verified badge by SelfShop.',
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
            'The verified badge has been removed from your supplier account by SelfShop.',
            'warning',
            ['event' => 'vendor_badge_removed', 'vendor_id' => $vendor->id],
            '/vendor/profile'
        );

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Verified badge removed']);
        }
        return redirect()->back()->with('message', 'Verified badge removed.');
    }

    /**
     * Permanently delete a vendor and all related data.
     * DELETE /admin/vendors/{vendor}
     */
    public function destroy(Vendor $vendor)
    {
        // Revoke wholesaler status on the user (keep the user account)
        $user = $vendor->user;
        if ($user) {
            $user->is_verified_wholesaler = false;
            $user->save();
        }

        // Cascade-delete all related records
        $vendor->followers()->delete();
        $vendor->payouts()->delete();
        $vendor->payoutRequests()->delete();
        $vendor->payoutAccounts()->delete();
        $vendor->earnings()->delete();
        $vendor->shippingMethods()->delete();
        $vendor->kycDocuments()->delete();
        $vendor->warehouses()->delete();
        $vendor->products()->delete();

        $companyName = $vendor->company_name;
        $vendor->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => "Supplier '{$companyName}' deleted."]);
        }
        return redirect()->route('admin.vendors.index')->with('message', "Supplier '{$companyName}' has been permanently deleted.");
    }
}

