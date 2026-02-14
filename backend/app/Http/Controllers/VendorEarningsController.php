<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\VendorEarning;
use App\Services\VendorCommissionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VendorEarningsController extends Controller
{
    public function __construct(
        protected VendorCommissionService $commissionService
    ) {}

    private function getVendor()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }
        return $user->vendor;
    }

    /**
     * GET /vendor/earnings/summary
     * Total sales, commissions, net, pending, available, paid.
     * Cached 60s to avoid running ensureEarnings + heavy sums on every request.
     */
    public function summary(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $cacheKey = 'vendor:earnings:summary:' . $vendor->id;
        $data = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($vendor) {
            $this->ensureEarningsForVendorOrders($vendor->id);

            $total_sales = (float) $vendor->earnings()->sum('line_total');
            $total_commission = (float) $vendor->earnings()->sum('commission_amount');
            $net_earnings = (float) $vendor->earnings()->sum('net_amount');
            $pending_balance = (float) $vendor->earnings()->where('status', 'pending')->sum('net_amount');
            $available_balance = (float) $vendor->earnings()
                ->where('status', 'available')
                ->selectRaw('SUM(net_amount - COALESCE(paid_amount, 0)) as total')
                ->value('total') ?? 0;
            $paid_total = (float) $vendor->earnings()->sum(DB::raw('COALESCE(paid_amount, 0)'));

            $pending_request_amount = $vendor->payoutRequests()
                ->where('status', 'pending')
                ->sum('amount');

            return [
                'total_sales' => $total_sales,
                'total_commission' => $total_commission,
                'net_earnings' => $net_earnings,
                'pending_balance' => $pending_balance,
                'available_balance' => $available_balance,
                'paid_total' => $paid_total,
                'pending_payout_request_amount' => round($pending_request_amount, 2),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $data,
        ]);
    }

    /**
     * GET /vendor/earnings
     * Paginated list of earnings with order info.
     * Does not run ensureEarnings (summary does); keeps list fast.
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $query = $vendor->earnings()
            ->with(['order:id,invoiceID,orderDate,status', 'orderProduct:id,productName,quantity,productPrice'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->input('per_page', 15), 5), 50);
        $items = $query->paginate($perPage);

        $items->getCollection()->transform(function ($e) {
            return [
                'id' => $e->id,
                'order_id' => $e->order_id,
                'order' => $e->order ? [
                    'invoiceID' => $e->order->invoiceID,
                    'orderDate' => $e->order->orderDate,
                    'status' => $e->order->status,
                ] : null,
                'product_name' => $e->orderProduct?->productName,
                'quantity' => $e->orderProduct?->quantity,
                'line_total' => (float) $e->line_total,
                'commission_percent' => (float) $e->commission_percent,
                'commission_amount' => (float) $e->commission_amount,
                'net_amount' => (float) $e->net_amount,
                'status' => $e->status,
                'created_at' => $e->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => [
                'earnings' => $items->items(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                ],
            ],
        ]);
    }

    /**
     * Only sync orders that have vendor products but no earnings rows yet.
     * This avoids re-syncing on every page load and makes the earnings page fast.
     */
    private function ensureEarningsForVendorOrders(int $vendorId): void
    {
        $syncedOrderProductIds = VendorEarning::where('vendor_id', $vendorId)->pluck('order_product_id');

        $orderIds = Order::whereHas('orderproducts', function ($q) use ($vendorId, $syncedOrderProductIds) {
            $q->whereHas('product', fn ($p) => $p->where('vendor_id', $vendorId))
                ->when($syncedOrderProductIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $syncedOrderProductIds));
        })->pluck('id');

        foreach ($orderIds as $orderId) {
            $this->commissionService->syncEarningsForOrder($orderId);
        }
    }
}
