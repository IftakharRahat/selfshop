<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Orderproduct;
use App\Models\Vendor;
use App\Models\VendorEarning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminVendorReportController extends Controller
{
    /**
     * GET /admin/vendor-reports/profit-commission
     * Profit & commission report by vendor (aggregated from vendor_earnings).
     */
    public function profitCommission(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));

        $items = VendorEarning::whereBetween(DB::raw('DATE(vendor_earnings.created_at)'), [$from, $to])
            ->join('vendors', 'vendor_earnings.vendor_id', '=', 'vendors.id')
            ->select(
                'vendor_earnings.vendor_id',
                'vendors.company_name',
                DB::raw('SUM(vendor_earnings.line_total) as total_sales'),
                DB::raw('SUM(vendor_earnings.commission_amount) as total_commission'),
                DB::raw('SUM(vendor_earnings.net_amount) as net_earnings'),
                DB::raw('COUNT(DISTINCT vendor_earnings.order_id) as order_count')
            )
            ->groupBy('vendor_earnings.vendor_id', 'vendors.company_name')
            ->orderByDesc('total_sales')
            ->get();

        return response()->json([
            'status' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'by_vendor' => $items->map(fn ($r) => [
                    'vendor_id' => $r->vendor_id,
                    'company_name' => $r->company_name,
                    'total_sales' => (float) $r->total_sales,
                    'total_commission' => (float) $r->total_commission,
                    'net_earnings' => (float) $r->net_earnings,
                    'order_count' => (int) $r->order_count,
                ]),
            ],
        ]);
    }

    /**
     * GET /admin/vendor-reports/order-analytics
     * Order & return analytics: orders containing vendor products, grouped by status and optionally by vendor.
     */
    public function orderAnalytics(Request $request)
    {
        $from = $request->input('from', now()->subDays(30)->format('Y-m-d'));
        $to = $request->input('to', now()->format('Y-m-d'));
        $vendorId = $request->input('vendor_id'); // optional filter

        $orderIdsQuery = Order::whereBetween('orderDate', [$from, $to])->select('id');
        $orderIds = Orderproduct::join('products', 'orderproducts.product_id', '=', 'products.id')
            ->whereNotNull('products.vendor_id')
            ->when($vendorId, fn ($q) => $q->where('products.vendor_id', $vendorId))
            ->whereIn('orderproducts.order_id', $orderIdsQuery)
            ->pluck('orderproducts.order_id')
            ->unique();

        $orders = Order::whereIn('id', $orderIds)->get();

        $byStatus = $orders->groupBy('status')->map(fn ($group) => $group->count());
        $returnCount = $byStatus->get('Return', 0);

        $byVendor = Orderproduct::join('products', 'orderproducts.product_id', '=', 'products.id')
            ->whereIn('orderproducts.order_id', $orderIds)
            ->whereNotNull('products.vendor_id')
            ->select(
                'products.vendor_id',
                DB::raw('COUNT(DISTINCT orderproducts.order_id) as order_count'),
                DB::raw('SUM(orderproducts.quantity) as total_quantity')
            )
            ->groupBy('products.vendor_id')
            ->get();

        $vendorNames = Vendor::whereIn('id', $byVendor->pluck('vendor_id'))->pluck('company_name', 'id');

        return response()->json([
            'status' => true,
            'data' => [
                'from' => $from,
                'to' => $to,
                'total_orders' => $orders->count(),
                'by_status' => $byStatus->all(),
                'return_count' => $returnCount,
                'by_vendor' => $byVendor->map(fn ($r) => [
                    'vendor_id' => $r->vendor_id,
                    'company_name' => $vendorNames->get($r->vendor_id, '—'),
                    'order_count' => (int) $r->order_count,
                    'total_quantity' => (int) $r->total_quantity,
                ]),
            ],
        ]);
    }
}
