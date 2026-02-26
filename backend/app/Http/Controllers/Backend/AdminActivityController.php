<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorPayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class AdminActivityController extends Controller
{
    /**
     * Top-bar activity feed for admin panel.
     * Aggregates recent user/supplier activity into a single stream.
     */
    public function feed(Request $request)
    {
        $limit = min(max((int) $request->input('limit', 20), 5), 50);

        $activities = collect()
            ->merge($this->buildUserActivities())
            ->merge($this->buildSupplierActivities())
            ->merge($this->buildSupplierProductActivities())
            ->merge($this->buildPayoutActivities())
            ->merge($this->buildOrderActivities())
            ->sortByDesc('created_ts')
            ->values()
            ->take($limit)
            ->map(function (array $item) {
                unset($item['created_ts']);
                return $item;
            })
            ->values();

        return response()->json([
            'status' => true,
            'data' => $activities,
            'summary' => [
                'pending_suppliers' => Vendor::where('status', 'pending')->count(),
                'pending_payout_requests' => VendorPayoutRequest::whereIn('status', ['pending', 'Pending'])->count(),
                'today_users' => User::whereDoesntHave('vendor')->whereDate('created_at', now()->toDateString())->count(),
                'today_orders' => Order::whereDate('created_at', now()->toDateString())->count(),
            ],
        ]);
    }

    private function buildUserActivities(): Collection
    {
        return User::query()
            ->whereDoesntHave('vendor')
            ->select('id', 'name', 'email', 'created_at')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(function (User $user) {
                $name = trim((string) ($user->name ?: $user->email ?: 'User #' . $user->id));
                return [
                    'id' => 'user-registered-' . $user->id,
                    'scope' => 'user',
                    'title' => 'New user registered',
                    'message' => $name . ' joined the platform.',
                    'url' => route('admin.users.index'),
                    'created_at' => optional($user->created_at)->toIso8601String(),
                    'created_ts' => optional($user->created_at)->timestamp ?? 0,
                ];
            });
    }

    private function buildSupplierActivities(): Collection
    {
        return Vendor::query()
            ->with('user:id,email')
            ->select('id', 'company_name', 'contact_email', 'user_id', 'status', 'created_at')
            ->latest('created_at')
            ->limit(30)
            ->get()
            ->map(function (Vendor $vendor) {
                $supplierName = trim((string) ($vendor->company_name ?: $vendor->contact_email ?: ($vendor->user?->email ?: 'Supplier #' . $vendor->id)));
                return [
                    'id' => 'supplier-registered-' . $vendor->id,
                    'scope' => 'supplier',
                    'title' => 'Supplier activity',
                    'message' => $supplierName . ' is currently ' . strtolower((string) ($vendor->status ?: 'pending')) . '.',
                    'url' => route('admin.vendors.show', $vendor->id),
                    'created_at' => optional($vendor->created_at)->toIso8601String(),
                    'created_ts' => optional($vendor->created_at)->timestamp ?? 0,
                ];
            });
    }

    private function buildSupplierProductActivities(): Collection
    {
        return Product::query()
            ->with('vendor:id,company_name')
            ->whereNotNull('vendor_id')
            ->select('id', 'vendor_id', 'ProductName', 'vendor_approval_status', 'created_at')
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(function (Product $product) {
                $supplierName = $product->vendor?->company_name ?: 'Supplier #' . $product->vendor_id;
                $status = strtolower((string) ($product->vendor_approval_status ?: 'pending'));
                return [
                    'id' => 'supplier-product-' . $product->id,
                    'scope' => 'supplier',
                    'title' => 'Supplier product update',
                    'message' => $supplierName . ' submitted "' . ($product->ProductName ?: 'Product') . '" (' . $status . ').',
                    'url' => route('admin.vendor-products.index'),
                    'created_at' => optional($product->created_at)->toIso8601String(),
                    'created_ts' => optional($product->created_at)->timestamp ?? 0,
                ];
            });
    }

    private function buildPayoutActivities(): Collection
    {
        return VendorPayoutRequest::query()
            ->with('vendor:id,company_name')
            ->select('id', 'vendor_id', 'amount', 'status', 'created_at')
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(function (VendorPayoutRequest $request) {
                $supplierName = $request->vendor?->company_name ?: 'Supplier #' . $request->vendor_id;
                $amount = number_format((float) $request->amount, 2);
                $status = strtolower((string) ($request->status ?: 'pending'));

                return [
                    'id' => 'supplier-payout-' . $request->id,
                    'scope' => 'supplier',
                    'title' => 'Supplier payout request',
                    'message' => $supplierName . ' requested payout Tk ' . $amount . ' (' . $status . ').',
                    'url' => route('admin.view-vendor-payout-requests.status', ['status' => 'Pending']),
                    'created_at' => optional($request->created_at)->toIso8601String(),
                    'created_ts' => optional($request->created_at)->timestamp ?? 0,
                ];
            });
    }

    private function buildOrderActivities(): Collection
    {
        return Order::query()
            ->with('users:id,name,email')
            ->select('id', 'invoiceID', 'user_id', 'status', 'subTotal', 'created_at')
            ->latest('created_at')
            ->limit(35)
            ->get()
            ->map(function (Order $order) {
                $customerName = trim((string) ($order->users?->name ?: $order->users?->email ?: ('User #' . $order->user_id)));
                $invoiceId = trim((string) ($order->invoiceID ?: ('Order #' . $order->id)));
                $amount = number_format((float) ($order->subTotal ?? 0), 2);

                return [
                    'id' => 'order-created-' . $order->id,
                    'scope' => 'user',
                    'title' => 'New order placed',
                    'message' => $invoiceId . ' by ' . $customerName . ' (Tk ' . $amount . ').',
                    'url' => url('admin_order/view/' . $order->id),
                    'created_at' => optional($order->created_at)->toIso8601String(),
                    'created_ts' => optional($order->created_at)->timestamp ?? 0,
                ];
            });
    }
}

