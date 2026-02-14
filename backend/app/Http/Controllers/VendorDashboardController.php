<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\VendorEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class VendorDashboardController extends Controller
{
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
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $vendorId = $vendor->id;
        $cacheKey = "vendor:dashboard:{$vendorId}";
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

            // Total sales & last month / this month from vendor_earnings
            $total_sales = (float) VendorEarning::where('vendor_id', $vendorId)->sum('line_total');
            $this_month_sales = (float) VendorEarning::where('vendor_id', $vendorId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$thisMonthStart, $thisMonthEnd])
                ->sum('line_total');
            $last_month_sales = (float) VendorEarning::where('vendor_id', $vendorId)
                ->whereBetween(DB::raw('DATE(created_at)'), [$lastMonthStart, $lastMonthEnd])
                ->sum('line_total');

            // Orders this month by status
            $orderIdsThisMonth = Order::whereHas('orderproducts.product', fn ($q) => $q->where('vendor_id', $vendorId))
                ->whereBetween('orderDate', [$thisMonthStart, $thisMonthEnd])
                ->pluck('id');
            $orders_by_status = Order::whereIn('id', $orderIdsThisMonth)
                ->select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->pluck('count', 'status')
                ->all();

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
                ->where(DB::raw('created_at'), '>=', now()->subMonths(5)->startOfMonth())
                ->select(
                    DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                    DB::raw('SUM(line_total) as total')
                )
                ->groupBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->orderBy(DB::raw('DATE_FORMAT(created_at, "%Y-%m")'))
                ->get()
                ->map(fn ($r) => ['month' => $r->month, 'total' => (float) $r->total]);

            // Top 12 products by sales (from vendor_earnings + orderproducts)
            $top_products = VendorEarning::where('vendor_earnings.vendor_id', $vendorId)
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
                    DB::raw('SUM(vendor_earnings.line_total) as total_sales'),
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
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->ProductName,
                    'slug' => $p->ProductSlug,
                    'image' => $p->ViewProductImage ?: $p->ProductImage,
                    'price' => (float) ($p->ProductSalePrice ?: $p->ProductRegularPrice),
                    'total_sales' => (float) $p->total_sales,
                    'total_quantity' => (int) $p->total_quantity,
                ]);

            return [
                'product_count' => $product_count,
                'total_orders' => $total_orders,
                'total_sales' => round($total_sales, 2),
                'this_month_sales' => round($this_month_sales, 2),
                'last_month_sales' => round($last_month_sales, 2),
                'orders_this_month_by_status' => $orders_by_status,
                'category_wise_product_count' => $category_counts,
                'sales_chart' => $sales_chart->all(),
                'top_products' => $top_products->all(),
            ];
        });

        return response()->json([
            'status' => true,
            'data' => $payload,
        ]);
    }
}
