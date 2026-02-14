<?php

namespace App\Http\Controllers;

use App\Models\VendorPayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class VendorPayoutRequestController extends Controller
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
     * GET /vendor/payout-requests
     */
    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $requests = $vendor->payoutRequests()
            ->with('payoutAccount:id,channel_type,account_name,account_number')
            ->orderByDesc('created_at')
            ->paginate(min(max((int) $request->input('per_page', 15), 5), 50));

        return response()->json([
            'status' => true,
            'data' => [
                'payout_requests' => $requests->items(),
                'pagination' => [
                    'current_page' => $requests->currentPage(),
                    'last_page' => $requests->lastPage(),
                    'per_page' => $requests->perPage(),
                    'total' => $requests->total(),
                ],
            ],
        ]);
    }

    /**
     * POST /vendor/payout-requests
     */
    public function store(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1',
            'payout_account_id' => 'nullable|integer|exists:vendor_payout_accounts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $payoutAccountId = $request->input('payout_account_id');
        if ($payoutAccountId) {
            $account = $vendor->payoutAccounts()->find($payoutAccountId);
            if (!$account) {
                return response()->json(['status' => false, 'message' => 'Payout account not found'], 404);
            }
        } else {
            $default = $vendor->payoutAccounts()->where('is_default', true)->first();
            $payoutAccountId = $default?->id;
        }

        $availableBalance = (float) ($vendor->earnings()
            ->where('status', 'available')
            ->selectRaw('SUM(net_amount - COALESCE(paid_amount, 0)) as total')
            ->value('total') ?? 0);
        $amount = round((float) $request->amount, 2);

        if ($amount > $availableBalance) {
            return response()->json([
                'status' => false,
                'message' => 'Amount exceeds available balance. Available: ' . number_format($availableBalance, 2),
            ], 422);
        }

        if ($vendor->payoutRequests()->where('status', 'pending')->exists()) {
            return response()->json([
                'status' => false,
                'message' => 'You already have a pending payout request. Wait for it to be processed.',
            ], 422);
        }

        $payoutRequest = VendorPayoutRequest::create([
            'vendor_id' => $vendor->id,
            'payout_account_id' => $payoutAccountId,
            'amount' => $amount,
            'status' => 'pending',
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payout request submitted',
            'data' => ['payout_request' => $payoutRequest->load('payoutAccount')],
        ], 201);
    }

    /**
     * GET /vendor/payouts
     * Payout history (completed payouts).
     */
    public function payouts(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $payouts = $vendor->payouts()
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->paginate(min(max((int) $request->input('per_page', 15), 5), 50));

        return response()->json([
            'status' => true,
            'data' => [
                'payouts' => $payouts->getCollection()->map(fn($p) => [
                    'id' => $p->id,
                    'amount' => (float) $p->amount,
                    'status' => $p->status,
                    'reference' => $p->reference,
                    'paid_at' => $p->paid_at?->toIso8601String(),
                    'created_at' => $p->created_at?->toIso8601String(),
                ]),
                'pagination' => [
                    'current_page' => $payouts->currentPage(),
                    'last_page' => $payouts->lastPage(),
                    'per_page' => $payouts->perPage(),
                    'total' => $payouts->total(),
                ],
            ],
        ]);
    }
}
