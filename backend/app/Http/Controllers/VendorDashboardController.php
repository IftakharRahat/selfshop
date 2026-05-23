<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\VendorEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Models\Review;
use App\Models\VendorFollower;

class VendorDashboardController extends Controller
{
    private const PENDING_ORDER_STATUSES = [
        'Pending',
        'Confirmed',
        'Processing',
        'Packageing',
        'Packaging',
        'Ontheway',
        'OnDelivery',
    ];

    private const AVAILABLE_ORDER_STATUSES = [
        'Delivered',
        'Complete',
        'Shipped',
    ];

    // Need to include CommissionService
    private function getVendor()
    {
        $user = Auth::user();
        if (!$user || !$user->vendor) {
            return null;
        }
        return $user->vendor;
    }

    /**
     * GET /vendor/dashboard
     * Aggregated stats for vendor dashboard: products, orders, sales, categories, top products.
     */
    public function index(Request $request, \App\Services\VendorCommissionService $commissionService)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $vendorId = $vendor->id;
        $cacheKey = "vendor:dashboard:{$vendorId}";

        // Always ensure earnings are synced first before caching metrics
        $this->ensureEarningsForVendorOrders($vendorId, $commissionService);

        $payload = Cache::remember($cacheKey, now()->addSeconds(60), function () use ($vendor, $vendorId) {
            $now = now();
            $thisMonthStart = $now->copy()->startOfMonth()->format('Y-m-d');
            $thisMonthEnd = $now->format('Y-m-d');
            $lastMonthStart = $now->copy()->subMonth()->startOfMonth()->format('Y-m-d');
            $lastMonthEnd = $now->copy()->subMonth()->endOfMonth()->format('Y-m-d');

            // Product count
            $product_count = $vendor->products()->count();

            // Total orders (orders containing at least one vendor product)
            $total_orders = Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))->count();

            // Pending amount should reflect active, pre-delivery orders only.
            $pending_amount = (float) VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::PENDING_ORDER_STATUSES)
                ->sum('net_amount');

            // Sales should use real delivered/completed order statuses to avoid stale earning status issues.
            $total_sales = (float) VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::AVAILABLE_ORDER_STATUSES)
                ->sum('net_amount');

            $this_month_sales = (float) VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::AVAILABLE_ORDER_STATUSES)
                ->whereBetween(DB::raw('DATE(vendor_earnings.created_at)'), [$thisMonthStart, $thisMonthEnd])
                ->sum('net_amount');

            $last_month_sales = (float) VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::AVAILABLE_ORDER_STATUSES)
                ->whereBetween(DB::raw('DATE(vendor_earnings.created_at)'), [$lastMonthStart, $lastMonthEnd])
                ->sum('net_amount');

            // Orders this month by status
            $orderIdsThisMonth = Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))
                ->whereBetween('orderDate', [$thisMonthStart, $thisMonthEnd])
                ->pluck('id');
            $orders_by_status = Order::whereIn('id', $orderIdsThisMonth)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->all();

            $normalized_order_summary = [
                'new_order' => (int) (($orders_by_status['Pending'] ?? 0) + ($orders_by_status['New'] ?? 0)),
                'accepted' => (int) ($orders_by_status['Confirmed'] ?? 0),
                'processing' => (int) ($orders_by_status['Processing'] ?? 0),
                'packaging' => (int) (($orders_by_status['Packageing'] ?? 0) + ($orders_by_status['Packaging'] ?? 0)),
                'on_delivery' => (int) (($orders_by_status['Ontheway'] ?? 0) + ($orders_by_status['OnDelivery'] ?? 0)),
                'delivered' => (int) (($orders_by_status['Delivered'] ?? 0) + ($orders_by_status['Complete'] ?? 0)),
                'cancelled' => (int) (($orders_by_status['Canceled'] ?? 0) + ($orders_by_status['Cancelled'] ?? 0)),
                'rejected' => (int) ($orders_by_status['Rejected'] ?? 0),
                'failed' => (int) ($orders_by_status['Failed'] ?? 0),
            ];

            // Category-wise product count (vendor's products)
            $category_counts = $vendor->products()
                ->join('categories', 'products.category_id', '=', 'categories.id')
                ->select('categories.id', 'categories.category_name')
                ->selectRaw('COUNT(products.id) as product_count')
                ->groupBy('categories.id', 'categories.category_name')
                ->orderByDesc('product_count')
                ->get()
                ->map(fn ($r) => ['category_name' => $r->category_name ?? $r->id, 'product_count' => (int) $r->product_count]);

            // Sales stat: last 6 months monthly totals for simple chart
            $sales_chart = VendorEarning::where('vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::AVAILABLE_ORDER_STATUSES)
                ->where('vendor_earnings.created_at', '>=', now()->subMonths(5)->startOfMonth())
                ->select(
                    DB::raw('DATE_FORMAT(vendor_earnings.created_at, "%Y-%m") as month'),
                    DB::raw('SUM(net_amount) as total')
                )
                ->groupBy(DB::raw('DATE_FORMAT(vendor_earnings.created_at, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(vendor_earnings.created_at, "%Y-%m")'))
                ->get()
                ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total]);

            // Top 12 products by sales (from vendor_earnings + orderproducts)
            $top_products = VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
                ->join('orders', 'vendor_earnings.order_id', '=', 'orders.id')
                ->whereIn('orders.status', self::AVAILABLE_ORDER_STATUSES)
                ->join('orderproducts', 'vendor_earnings.order_product_id', '=', 'orderproducts.id')
                ->join('products', 'orderproducts.product_id', '=', 'products.id')
                ->select(
                    'products.id',
                    'products.ProductName',
                    'products.ProductSlug',
                    'products.ProductImage',
                    'products.ViewProductImage',
                    'products.ProductRegularPrice',
                    'products.ProductSalePrice',
                    DB::raw('SUM(vendor_earnings.net_amount) as total_sales'),
                    DB::raw('SUM(orderproducts.quantity) as total_quantity')
                )
                ->groupBy(
                    'products.id',
                    'products.ProductName',
                    'products.ProductSlug',
                    'products.ProductImage',
                    'products.ViewProductImage',
                    'products.ProductRegularPrice',
                    'products.ProductSalePrice'
                )
                ->orderByDesc('total_sales')
                ->limit(12)
                ->get()
                ->map(function ($p) {
                    $productRating = (float) Review::where('product_id', $p->id)->avg('rating');
                    return [
                        'id' => $p->id,
                        'name' => $p->ProductName,
                        'slug' => $p->ProductSlug,
                        'image' => $p->ViewProductImage ?: $p->ProductImage,
                        'price' => (float) ($p->ProductSalePrice ?: $p->ProductRegularPrice),
                        'total_sales' => (float) $p->total_sales,
                        'total_quantity' => (int) $p->total_quantity,
                        'avg_rating' => round($productRating, 1),
                    ];
                });

            // Vendor rating (Average of all reviews for all products of this vendor)
            $avg_rating = (float) Review::whereIn('product_id', function ($query) use ($vendorId) {
                $query->select('id')->from('products')->where('vendor_id', $vendorId);
            })->avg('rating');

            // Total followers
            $total_followers = (int) VendorFollower::where('vendor_id', $vendorId)->count();

            return [
                'product_count' => $product_count,
                'total_orders' => $total_orders,
                'pending_amount' => round($pending_amount, 2),
                'total_sales' => round($total_sales, 2),
                'this_month_sales' => round($this_month_sales, 2),
                'last_month_sales' => round($last_month_sales, 2),
                'orders_this_month_by_status' => $orders_by_status,
                'orders_this_month_summary' => $normalized_order_summary,
                'category_wise_product_count' => $category_counts,
                'sales_chart' => $sales_chart->all(),
                'top_products' => $top_products->all(),
                'avg_rating' => round($avg_rating, 1),
                'total_followers' => $total_followers,
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $payload,
        ]);
    }

    private function ensureEarningsForVendorOrders(int $vendorId, \App\Services\VendorCommissionService $commissionService): void
    {
        $syncedOrderProductIds = VendorEarning::where('vendor_id', $vendorId)->pluck('order_product_id');

        $orderIds = Order::whereHas('orderproducts', function ($q) use ($vendorId, $syncedOrderProductIds) {
            $q->whereHas('product', fn ($p) => $p->where('vendor_id', $vendorId))
                ->when($syncedOrderProductIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $syncedOrderProductIds));
        })->pluck('id');

        foreach ($orderIds as $orderId) {
            $commissionService->syncEarningsForOrder($orderId);
        }
    }
}
