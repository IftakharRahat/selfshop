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
        $branding = $request->input('branding', '');

        $vendorsQuery = Vendor::with('user')
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($branding === 'pending', function ($query) {
                $query->where(function ($q) {
                    $q->whereNotNull('pending_logo_path')->orWhereNotNull('pending_banner_path');
                });
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
            'pending_branding' => Vendor::where(function ($q) {
                $q->whereNotNull('pending_logo_path')->orWhereNotNull('pending_banner_path');
            })->count(),
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
     * Supplier sales overview page.
     * GET /admin/vendors/{vendor}/sales-summary
     */
    public function salesSummary(Vendor $vendor)
    {
        $vendor->load(['user', 'payoutAccounts']);
        $vendorId = $vendor->id;

        // ── Counts ──
        $productCount = $vendor->products()->count();
        $totalOrders = \App\Models\Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))->count();
        $ordersByStatus = \App\Models\Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))
            ->select('status', \DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->all();

        // ── Earnings ──
        $totalSales = (float) $vendor->earnings()->sum('line_total');
        $totalCommission = (float) $vendor->earnings()->sum('commission_amount');
        $netEarnings = (float) $vendor->earnings()->sum('net_amount');
        $pendingBalance = (float) $vendor->earnings()->where('status', 'pending')->sum('net_amount');
        $rawAvailable = (float) ($vendor->earnings()
            ->where('status', 'available')
            ->selectRaw('SUM(net_amount - COALESCE(paid_amount, 0)) as total')
            ->value('total') ?? 0);
        $paidTotal = (float) $vendor->earnings()->sum(\DB::raw('COALESCE(paid_amount, 0)'));
        $pendingPayoutAmount = (float) $vendor->payoutRequests()->where('status', 'pending')->sum('amount');
        $availableBalance = max(0, $rawAvailable - $pendingPayoutAmount);

        // ── Top 10 delivered products ──
        $topProducts = \App\Models\VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
            ->where('vendor_earnings.status', 'available')
            ->join('orderproducts', 'vendor_earnings.order_product_id', '=', 'orderproducts.id')
            ->join('products', 'orderproducts.product_id', '=', 'products.id')
            ->select(
                'products.ProductName',
                \DB::raw('SUM(orderproducts.quantity) as total_qty'),
                \DB::raw('SUM(vendor_earnings.line_total) as total_revenue'),
                \DB::raw('SUM(vendor_earnings.net_amount) as total_net')
            )
            ->groupBy('products.id', 'products.ProductName')
            ->orderByDesc('total_revenue')
            ->limit(10)
            ->get();

        // ── Recent orders (paginated, 10 per page) ──
        $recentOrders = \App\Models\Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))
            ->with('customers')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'orders_page')
            ->withQueryString();

        // ── All products (paginated, 10 per page) ──
        $allProducts = $vendor->products()
            ->select('id', 'ProductName', 'ProductRegularPrice', 'ProductSalePrice', 'qty', 'status')
            ->orderByDesc('id')
            ->paginate(10, ['*'], 'products_page')
            ->withQueryString();

        return view('backend.content.vendors.sales_summary', compact(
            'vendor', 'productCount', 'totalOrders', 'ordersByStatus',
            'totalSales', 'totalCommission', 'netEarnings',
            'pendingBalance', 'availableBalance', 'paidTotal',
            'pendingPayoutAmount', 'topProducts', 'recentOrders', 'allProducts'
        ));
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
     * Approve pending logo/banner changes for a vendor.
     * POST /admin/vendors/{vendor}/approve-branding
     * Accepts ?type=logo|banner (defaults to both)
     */
    public function approveBranding(Request $request, Vendor $vendor)
    {
        $type = $request->input('type', 'all');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');
        $updated = false;

        // Approve pending logo
        if (in_array($type, ['logo', 'all']) && $vendor->pending_logo_path) {
            if ($vendor->logo_path) {
                $oldPath = str_replace($r2BaseUrl . '/', '', $vendor->logo_path);
                \Storage::disk('r2')->delete($oldPath);
            }
            $vendor->logo_path = $vendor->pending_logo_path;
            $vendor->pending_logo_path = null;
            $updated = true;
        }

        // Approve pending banner
        if (in_array($type, ['banner', 'all']) && $vendor->pending_banner_path) {
            if ($vendor->banner_path) {
                $oldPath = str_replace($r2BaseUrl . '/', '', $vendor->banner_path);
                \Storage::disk('r2')->delete($oldPath);
            }
            $vendor->banner_path = $vendor->pending_banner_path;
            $vendor->pending_banner_path = null;
            $updated = true;
        }

        if ($updated) {
            $vendor->save();
        }

        $label = $type === 'logo' ? 'Logo' : ($type === 'banner' ? 'Banner' : 'Branding');
        return redirect()->back()->with('message', "{$label} change approved and now live.");
    }

    /**
     * Reject pending logo/banner changes for a vendor.
     * POST /admin/vendors/{vendor}/reject-branding
     * Accepts ?type=logo|banner (defaults to both)
     */
    public function rejectBranding(Request $request, Vendor $vendor)
    {
        $type = $request->input('type', 'all');
        $r2BaseUrl = rtrim(config('filesystems.disks.r2.url'), '/');

        // Reject pending logo
        if (in_array($type, ['logo', 'all']) && $vendor->pending_logo_path) {
            $path = str_replace($r2BaseUrl . '/', '', $vendor->pending_logo_path);
            \Storage::disk('r2')->delete($path);
            $vendor->pending_logo_path = null;
        }

        // Reject pending banner
        if (in_array($type, ['banner', 'all']) && $vendor->pending_banner_path) {
            $path = str_replace($r2BaseUrl . '/', '', $vendor->pending_banner_path);
            \Storage::disk('r2')->delete($path);
            $vendor->pending_banner_path = null;
        }

        $vendor->save();

        $label = $type === 'logo' ? 'Logo' : ($type === 'banner' ? 'Banner' : 'Branding');
        return redirect()->back()->with('message', "{$label} change rejected.");
    }

    /**
     * Approve a KYC document.
     * POST /admin/vendors/kyc/{document}/approve
     */
    public function approveKyc(\App\Models\VendorKycDocument $document)
    {
        $document->status = 'approved';
        $document->verified_at = now();
        $document->verified_by = Auth::guard('admin')->id();
        $document->review_notes = null;
        $document->save();

        return redirect()->back()->with('message', "KYC document ({$document->document_type}) approved.");
    }

    /**
     * Reject a KYC document.
     * POST /admin/vendors/kyc/{document}/reject
     */
    public function rejectKyc(Request $request, \App\Models\VendorKycDocument $document)
    {
        $document->status = 'rejected';
        $document->verified_at = now();
        $document->verified_by = Auth::guard('admin')->id();
        $document->review_notes = $request->input('review_notes');
        $document->save();

        return redirect()->back()->with('message', "KYC document ({$document->document_type}) rejected.");
    }

    /**
     * Permanently delete a vendor and all related data.
     * DELETE /admin/vendors/{vendor}
     */
    public function destroy(Vendor $vendor)
    {
        $user = $vendor->user;

        // Cascade-delete all related vendor records
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

        // Revoke all API tokens and delete the user account so they can no longer log in
        if ($user) {
            $user->tokens()->delete();          // Revoke all Sanctum tokens
            $user->delete();                     // Remove the user account entirely
        }

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => "Supplier '{$companyName}' deleted."]);
        }
        return redirect()->route('admin.vendors.index')->with('message', "Supplier '{$companyName}' has been permanently deleted.");
    }
    /**
     * Auto-login as the vendor's user account in the storefront.
     * GET /admin/vendor-autologin/{vendor}
     */
    public function autologin(Vendor $vendor)
    {
        $user = User::find($vendor->user_id);
        if (!$user) {
            return redirect()->back()->with('error', 'User account not found for this supplier.');
        }

        // Generate a Sanctum token for the target user (same as user autologin)
        $token = $user->createToken('admin-impersonate')->plainTextToken;

        // Redirect to the vendor panel (supplier dashboard) with the token
        $frontendUrl = rtrim(env('FRONTEND_URL', 'https://selfshop.com.bd'), '/');
        return redirect($frontendUrl . '/impersonate?token=' . urlencode($token) . '&redirect=/vendor');
    }
}

