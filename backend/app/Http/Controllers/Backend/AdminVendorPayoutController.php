<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\VendorEarning;
use App\Models\VendorPayout;
use App\Models\VendorPayoutRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminVendorPayoutController extends Controller
{
    /**
     * Show vendor payout requests page (Blade view with table and Approve/Reject).
     */
    public function showPage(Request $request, $status = null)
    {
        $statusSlug = $this->normalizeStatusSlug($status);
        $counts = [
            'pending' => VendorPayoutRequest::where('status', 'pending')->count(),
            'approved' => VendorPayoutRequest::where('status', 'approved')->count(),
            'rejected' => VendorPayoutRequest::where('status', 'rejected')->count(),
        ];
        $query = VendorPayoutRequest::with(['vendor:id,company_name,contact_email', 'payoutAccount'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');
        if ($statusSlug) {
            $query->where('status', $statusSlug);
        }
        $requests = $query->paginate(20);
        return view('backend.content.account.vendor_payout_requests', [
            'requests' => $requests,
            'counts' => $counts,
            'currentStatus' => $statusSlug,
        ]);
    }

    private function normalizeStatusSlug(?string $slug): ?string
    {
        if (!$slug) {
            return null;
        }
        $map = ['Pending' => 'pending', 'Success' => 'approved', 'Canceled' => 'rejected', 'pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'];
        return $map[$slug] ?? $slug;
    }

    /**
     * List all vendor payout requests (pending first). JSON API.
     */
    public function index(Request $request)
    {
        $query = VendorPayoutRequest::with(['vendor:id,company_name,contact_email', 'payoutAccount'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);
        $items = $query->paginate($perPage);

        return response()->json([
            'status' => true,
            'data' => [
                'payout_requests' => $items->items(),
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
     * Approve a payout request: create VendorPayout, mark earnings as paid.
     */
    public function approve(Request $request, $id)
    {
        $payoutRequest = VendorPayoutRequest::with('vendor')->findOrFail($id);
        if ($payoutRequest->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Request is not pending'], 422);
        }

        $amount = (float) $payoutRequest->amount;
        $vendorId = $payoutRequest->vendor_id;

        $availableSum = VendorEarning::where('vendor_id', $vendorId)
            ->where('status', 'available')
            ->sum('net_amount');

        if ($amount > $availableSum) {
            return response()->json([
                'status' => false,
                'message' => 'Available balance is insufficient. Available: ' . number_format($availableSum, 2),
            ], 422);
        }

        DB::transaction(function () use ($payoutRequest, $amount, $vendorId) {
            $payout = VendorPayout::create([
                'vendor_id' => $vendorId,
                'payout_request_id' => $payoutRequest->id,
                'amount' => $amount,
                'status' => 'completed',
                'reference' => $payoutRequest->id . '-' . now()->format('Ymd'),
                'paid_at' => now(),
                'admin_id' => Auth::id(),
            ]);

            $earnings = VendorEarning::where('vendor_id', $vendorId)
                ->where('status', 'available')
                ->orderBy('id')
                ->get();

            $remaining = $amount;
            foreach ($earnings as $e) {
                if ($remaining <= 0) {
                    break;
                }
                $net = (float) $e->net_amount;
                $alreadyPaid = (float) ($e->paid_amount ?? 0);
                $allocatable = $net - $alreadyPaid;
                if ($allocatable <= 0) {
                    continue;
                }
                $allocate = min($remaining, $allocatable);
                $newPaid = $alreadyPaid + $allocate;
                $e->paid_amount = $newPaid;
                $e->payout_id = $payout->id;
                if ($newPaid >= $net) {
                    $e->status = 'paid';
                }
                $e->save();
                $remaining -= $allocate;
            }

            $payoutRequest->update([
                'status' => 'approved',
                'processed_at' => now(),
                'processed_by' => Auth::id(),
            ]);
        });

        return response()->json([
            'status' => true,
            'message' => 'Payout approved and processed',
        ]);
    }

    /**
     * Reject a payout request.
     */
    public function reject(Request $request, $id)
    {
        $payoutRequest = VendorPayoutRequest::findOrFail($id);
        if ($payoutRequest->status !== 'pending') {
            return response()->json(['status' => false, 'message' => 'Request is not pending'], 422);
        }

        $payoutRequest->update([
            'status' => 'rejected',
            'admin_notes' => $request->input('admin_notes'),
            'processed_at' => now(),
            'processed_by' => Auth::id(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Payout request rejected',
        ]);
    }
}
