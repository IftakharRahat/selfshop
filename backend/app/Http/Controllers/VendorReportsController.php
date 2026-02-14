<?php

namespace App\Http\Controllers;

use App\Models\Orderproduct;
use App\Models\VendorEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorReportsController extends Controller
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
     * GET /vendor/reports/sales
     * Sales report: daily or monthly totals for the vendor.
     */
    public function sales(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $period = $request->input('period', 'day'); // day | month
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $query = VendorEarning::where('vendor_id', $vendor->id)
            ->whereBetween(DB::raw('DATE(created_at)'), [$from, $to]);

        if ($period === 'month') {
            $items = $query->select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as period'),
                DB::raw('SUM(line_total) as total_sales'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('SUM(net_amount) as net_earnings'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )->groupBy('period')->orderBy('period')->get();
        } else {
            $items = $query->select(
                DB::raw('DATE(created_at) as period'),
                DB::raw('SUM(line_total) as total_sales'),
                DB::raw('SUM(commission_amount) as total_commission'),
                DB::raw('SUM(net_amount) as net_earnings'),
                DB::raw('COUNT(DISTINCT order_id) as order_count')
            )->groupBy('period')->orderBy('period')->get();
        }

        return response()->json([
            'status' => true,
            'data' => [
                'period' => $period,
                'from' => $from,
                'to' => $to,
                'series' => $items->map(fn ($r) => [
                    'period' => $r->period,
                    'total_sales' => (float) $r->total_sales,
                    'total_commission' => (float) $r->total_commission,
                    'net_earnings' => (float) $r->net_earnings,
                    'order_count' => (int) $r->order_count,
                ]),
            ],
        ]);
    }

    /**
     * GET /vendor/reports/top-products
     * Top selling products by line total or quantity.
     */
    public function topProducts(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $limit = min(max((int) $request->input('limit', 10), 1), 50);

        $items = VendorEarning::where('vendor_id', $vendor->id)
            ->join('orderproducts', 'vendor_earnings.order_product_id', '=', 'orderproducts.id')
            ->select(
                'orderproducts.product_id',
                'orderproducts.productName',
                DB::raw('SUM(vendor_earnings.line_total) as total_sales'),
                DB::raw('SUM(orderproducts.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT vendor_earnings.order_id) as order_count')
            )
            ->groupBy('orderproducts.product_id', 'orderproducts.productName')
            ->orderByDesc('total_sales')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'top_products' => $items->map(fn ($r) => [
                    'product_id' => $r->product_id,
                    'product_name' => $r->productName,
                    'total_sales' => (float) $r->total_sales,
                    'total_quantity' => (int) $r->total_quantity,
                    'order_count' => (int) $r->order_count,
                ]),
            ],
        ]);
    }

    /**
     * GET /vendor/reports/sales-breakdown
     * Wholesale vs dropship (or by fulfillment_type) sales breakdown.
     */
    public function salesBreakdown(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $from = $request->input('from', now()->subDays(90)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $items = VendorEarning::where('vendor_earnings.vendor_id', $vendor->id)
            ->join('orderproducts', 'vendor_earnings.order_product_id', '=', 'orderproducts.id')
            ->whereBetween(DB::raw('DATE(vendor_earnings.created_at)'), [$from, $to])
            ->select(
                DB::raw('COALESCE(orderproducts.fulfillment_type, "standard") as fulfillment_type'),
                DB::raw('SUM(vendor_earnings.line_total) as total_sales'),
                DB::raw('SUM(vendor_earnings.net_amount) as net_earnings'),
                DB::raw('COUNT(DISTINCT vendor_earnings.order_id) as order_count')
            )
            ->groupBy('fulfillment_type')
            ->get();

        $breakdown = $items->mapWithKeys(fn ($r) => [
            $r->fulfillment_type => [
                'total_sales' => (float) $r->total_sales,
                'net_earnings' => (float) $r->net_earnings,
                'order_count' => (int) $r->order_count,
            ],
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'by_fulfillment_type' => $breakdown->all(),
            ],
        ]);
    }
}
