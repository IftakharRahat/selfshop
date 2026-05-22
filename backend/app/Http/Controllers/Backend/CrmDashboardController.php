<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Resellerinvoice;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorEarning;
use App\Models\VendorPayout;
use App\Models\VendorPayoutRequest;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrmDashboardController extends Controller
{
    /**
     * CRM analytics dashboard for admin.
     */
    public function index(Request $request)
    {
        $fromInput = trim((string) $request->input('from', ''));
        $toInput = trim((string) $request->input('to', ''));

        $fromDate = null;
        $toDate = null;

        try {
            if ($fromInput !== '') {
                $fromDate = Carbon::parse($fromInput)->startOfDay();
            }
        } catch (\Throwable $e) {
            $fromDate = null;
        }

        try {
            if ($toInput !== '') {
                $toDate = Carbon::parse($toInput)->endOfDay();
            }
        } catch (\Throwable $e) {
            $toDate = null;
        }

        if ($fromDate && $toDate && $fromDate->gt($toDate)) {
            [$fromDate, $toDate] = [$toDate->copy()->startOfDay(), $fromDate->copy()->endOfDay()];
        }

        $applyDateRangeDatetime = function ($query, string $column) use ($fromDate, $toDate) {
            if ($fromDate) {
                $query->where($column, '>=', $fromDate);
            }
            if ($toDate) {
                $query->where($column, '<=', $toDate);
            }
            return $query;
        };

        $applyDateRangeDateColumn = function ($query, string $column) use ($fromDate, $toDate) {
            if ($fromDate) {
                $query->whereDate($column, '>=', $fromDate->toDateString());
            }
            if ($toDate) {
                $query->whereDate($column, '<=', $toDate->toDateString());
            }
            return $query;
        };

        $applyDateRangeExpression = function ($query, string $expression) use ($fromDate, $toDate) {
            if ($fromDate) {
                $query->whereRaw($expression . ' >= ?', [$fromDate->toDateString()]);
            }
            if ($toDate) {
                $query->whereRaw($expression . ' <= ?', [$toDate->toDateString()]);
            }
            return $query;
        };

        $salesRevenueQuery = Order::query()
            ->where('status', 'Delivered')
            ->whereNotNull('orderDate');
        $applyDateRangeDateColumn($salesRevenueQuery, 'orderDate');
        $totalSalesRevenue = (float) $salesRevenueQuery
            ->sum(DB::raw('COALESCE(subTotal, 0) + COALESCE(paymentAmount, 0)'));

        $commissionRevenueQuery = VendorEarning::query()
            ->whereHas('order', function ($q) {
                $q->where('status', 'Delivered');
            });
        $applyDateRangeDatetime($commissionRevenueQuery, 'created_at');
        $totalCommissionRevenue = (float) $commissionRevenueQuery->sum('commission_amount');

        $subscriptionRevenueQuery = Resellerinvoice::query()
            ->whereRaw("LOWER(COALESCE(status, '')) = ?", ['paid'])
            ->whereNotNull('invoiceDate');
        $applyDateRangeExpression($subscriptionRevenueQuery, 'DATE(COALESCE(paymentDate, invoiceDate))');
        $totalSubscriptionRevenue = (float) $subscriptionRevenueQuery
            ->sum(DB::raw('CASE WHEN COALESCE(paid_amount, 0) > 0 THEN paid_amount ELSE COALESCE(payable_amount, 0) END'));

        $usersBaseQuery = User::doesntHave('vendor');
        $applyDateRangeDatetime($usersBaseQuery, 'created_at');

        $allUsers = (clone $usersBaseQuery)->count();
        $activeUsers = (clone $usersBaseQuery)->whereRaw("LOWER(COALESCE(membership_status, '')) = ?", ['paid'])->count();
        $paidUsers = (clone $usersBaseQuery)->whereRaw("LOWER(COALESCE(membership_status, '')) = ?", ['paid'])->count();
        $unpaidUsers = (clone $usersBaseQuery)->whereRaw("LOWER(COALESCE(membership_status, '')) = ?", ['unpaid'])->count();
        $totalUserAccountBalance = (float) (clone $usersBaseQuery)->sum('account_balance');

        $userAccounts = (clone $usersBaseQuery)
            ->select('id', 'name', 'email', 'phone', 'status', 'membership_status', 'account_balance', 'updated_at')
            ->orderByDesc('account_balance')
            ->limit(10)
            ->get();

        $suppliersBaseQuery = Vendor::query();
        $applyDateRangeDatetime($suppliersBaseQuery, 'created_at');

        $activeSuppliers = (clone $suppliersBaseQuery)->where('status', 'approved')->count();
        $pendingSuppliers = (clone $suppliersBaseQuery)->where('status', 'pending')->count();

        $supplierPaymentQuery = VendorPayout::query()
            ->where('status', 'completed')
            ->whereNotNull('created_at');
        $applyDateRangeExpression($supplierPaymentQuery, 'DATE(COALESCE(paid_at, created_at))');
        $totalSupplierPayment = (float) $supplierPaymentQuery
            ->sum('amount');

        $pendingSupplierPaymentQuery = VendorPayoutRequest::query()
            ->where('status', 'pending')
            ->whereNotNull('created_at');
        $applyDateRangeDatetime($pendingSupplierPaymentQuery, 'created_at');
        $pendingSupplierPayment = (float) $pendingSupplierPaymentQuery
            ->sum('amount');

        $supplierBalanceQuery = VendorEarning::query();
        $applyDateRangeDatetime($supplierBalanceQuery, 'created_at');
        $totalSupplierAccountBalance = (float) $supplierBalanceQuery
            ->sum(DB::raw('COALESCE(net_amount, 0) - COALESCE(paid_amount, 0)'));

        $supplierEarnings = VendorEarning::query()
            ->select(
                'vendor_id',
                DB::raw('SUM(COALESCE(line_total, 0)) AS sales_total'),
                DB::raw('SUM(COALESCE(commission_amount, 0)) AS commission_total'),
                DB::raw('SUM(COALESCE(net_amount, 0)) AS net_total'),
                DB::raw('SUM(COALESCE(paid_amount, 0)) AS paid_total'),
                DB::raw('COUNT(DISTINCT order_id) AS order_count')
            );
        $applyDateRangeDatetime($supplierEarnings, 'created_at');
        $supplierEarnings->groupBy('vendor_id');

        $supplierBaseQuery = Vendor::query()
            ->leftJoinSub($supplierEarnings, 'earnings', function ($join) {
                $join->on('vendors.id', '=', 'earnings.vendor_id');
            })
            ->leftJoin('users', 'users.id', '=', 'vendors.user_id')
            ->select(
                'vendors.id',
                'vendors.company_name',
                'vendors.status',
                'users.email AS user_email',
                'users.phone AS user_phone',
                DB::raw('COALESCE(earnings.sales_total, 0) AS sales_total'),
                DB::raw('COALESCE(earnings.commission_total, 0) AS commission_total'),
                DB::raw('COALESCE(earnings.net_total, 0) AS net_total'),
                DB::raw('COALESCE(earnings.order_count, 0) AS order_count'),
                DB::raw('COALESCE(earnings.net_total, 0) - COALESCE(earnings.paid_total, 0) AS account_balance')
            );
        $applyDateRangeDatetime($supplierBaseQuery, 'vendors.created_at');

        $supplierLeaderboard = (clone $supplierBaseQuery)
            ->orderByDesc('sales_total')
            ->limit(10)
            ->get();

        $supplierAccounts = (clone $supplierBaseQuery)
            ->orderByDesc('account_balance')
            ->limit(10)
            ->get();

        return view('backend.content.crm.dashboard', [
            'totalSalesRevenue' => $totalSalesRevenue,
            'totalCommissionRevenue' => $totalCommissionRevenue,
            'totalSubscriptionRevenue' => $totalSubscriptionRevenue,
            'allUsers' => $allUsers,
            'activeUsers' => $activeUsers,
            'paidUsers' => $paidUsers,
            'unpaidUsers' => $unpaidUsers,
            'totalUserAccountBalance' => $totalUserAccountBalance,
            'userAccounts' => $userAccounts,
            'activeSuppliers' => $activeSuppliers,
            'pendingSuppliers' => $pendingSuppliers,
            'totalSupplierPayment' => $totalSupplierPayment,
            'pendingSupplierPayment' => $pendingSupplierPayment,
            'totalSupplierAccountBalance' => $totalSupplierAccountBalance,
            'supplierLeaderboard' => $supplierLeaderboard,
            'supplierAccounts' => $supplierAccounts,
            'from' => $fromDate ? $fromDate->toDateString() : '',
            'to' => $toDate ? $toDate->toDateString() : '',
        ]);
    }

    /**
     * Full CRM users listing (paginated + filterable).
     */
    public function users(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $membership = trim((string) $request->input('membership', ''));

        $usersQuery = User::query()
            ->select('id', 'name', 'email', 'phone', 'status', 'membership_status', 'account_balance', 'created_at')
            ->when($status !== '', function ($q) use ($status) {
                $q->where('status', $status);
            })
            ->when($membership !== '', function ($q) use ($membership) {
                $q->whereRaw("LOWER(COALESCE(membership_status, '')) = ?", [strtolower($membership)]);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('id');

        $users = $usersQuery->paginate(30)->withQueryString();

        return view('backend.content.crm.users', [
            'users' => $users,
            'search' => $search,
            'status' => $status,
            'membership' => $membership,
            'allUsers' => User::count(),
            'activeUsers' => User::whereRaw("LOWER(COALESCE(membership_status, '')) = 'paid'")->count(),
            'paidUsers' => User::whereRaw("LOWER(COALESCE(membership_status, '')) = 'paid'")->count(),
            'unpaidUsers' => User::whereRaw("LOWER(COALESCE(membership_status, '')) = 'unpaid'")->count(),
            'expiredUsers' => User::whereNotNull('expire_date')->where('expire_date', '!=', '')->where('expire_date', '<', date('Y-m-d'))->count(),
            'totalUserAccountBalance' => (float) User::sum('account_balance'),
        ]);
    }

    /**
     * Full CRM suppliers listing (paginated + filterable).
     */
    public function suppliers(Request $request)
    {
        $search = trim((string) $request->input('search', ''));
        $status = strtolower(trim((string) $request->input('status', '')));

        $supplierEarnings = VendorEarning::query()
            ->select(
                'vendor_id',
                DB::raw('SUM(COALESCE(line_total, 0)) AS sales_total'),
                DB::raw('SUM(COALESCE(commission_amount, 0)) AS commission_total'),
                DB::raw('SUM(COALESCE(net_amount, 0)) AS net_total'),
                DB::raw('SUM(COALESCE(paid_amount, 0)) AS paid_total'),
                DB::raw('COUNT(DISTINCT order_id) AS order_count')
            )
            ->groupBy('vendor_id');

        $pendingPayouts = VendorPayoutRequest::query()
            ->select(
                'vendor_id',
                DB::raw('SUM(COALESCE(amount, 0)) AS pending_payout_total')
            )
            ->where('status', 'pending')
            ->groupBy('vendor_id');

        $suppliersQuery = Vendor::query()
            ->leftJoinSub($supplierEarnings, 'earnings', function ($join) {
                $join->on('vendors.id', '=', 'earnings.vendor_id');
            })
            ->leftJoinSub($pendingPayouts, 'pending_payouts', function ($join) {
                $join->on('vendors.id', '=', 'pending_payouts.vendor_id');
            })
            ->leftJoin('users', 'users.id', '=', 'vendors.user_id')
            ->select(
                'vendors.id',
                'vendors.company_name',
                'vendors.status',
                'vendors.contact_name',
                'vendors.contact_email',
                'vendors.contact_phone',
                'vendors.created_at',
                DB::raw('(SELECT COUNT(*) FROM products WHERE products.vendor_id = vendors.id) AS products_count'),
                'users.email AS user_email',
                DB::raw('COALESCE(earnings.sales_total, 0) AS sales_total'),
                DB::raw('COALESCE(earnings.commission_total, 0) AS commission_total'),
                DB::raw('COALESCE(earnings.net_total, 0) AS net_total'),
                DB::raw('COALESCE(earnings.order_count, 0) AS order_count'),
                DB::raw('COALESCE(earnings.net_total, 0) - COALESCE(earnings.paid_total, 0) AS gross_account_balance'),
                DB::raw('COALESCE(pending_payouts.pending_payout_total, 0) AS pending_payout_balance'),
                DB::raw('COALESCE(earnings.net_total, 0) - COALESCE(earnings.paid_total, 0) - COALESCE(pending_payouts.pending_payout_total, 0) AS account_balance')
            )
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'suspended'], true), function ($q) use ($status) {
                $q->where('vendors.status', $status);
            })
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('vendors.company_name', 'like', '%' . $search . '%')
                        ->orWhere('vendors.contact_name', 'like', '%' . $search . '%')
                        ->orWhere('vendors.contact_email', 'like', '%' . $search . '%')
                        ->orWhere('users.email', 'like', '%' . $search . '%');
                });
            })
            ->orderByDesc('vendors.id');

        $suppliers = $suppliersQuery->paginate(30)->withQueryString();

        $totalAvailableSupplierBalance = (float) VendorEarning::sum(DB::raw('COALESCE(net_amount, 0) - COALESCE(paid_amount, 0)'))
            - (float) VendorPayoutRequest::where('status', 'pending')->sum('amount');

        return view('backend.content.crm.suppliers', [
            'suppliers' => $suppliers,
            'search' => $search,
            'status' => $status,
            'activeSuppliers' => Vendor::where('status', 'approved')->count(),
            'pendingSuppliers' => Vendor::where('status', 'pending')->count(),
            'totalSuppliers' => Vendor::count(),
            'totalSupplierPayment' => (float) VendorPayout::where('status', 'completed')->sum('amount'),
            'pendingSupplierPayment' => (float) VendorPayoutRequest::where('status', 'pending')->sum('amount'),
            'totalSupplierAccountBalance' => $totalAvailableSupplierBalance,
            'totalProducts' => \App\Models\Product::whereNotNull('vendor_id')->count(),
        ]);
    }
}
