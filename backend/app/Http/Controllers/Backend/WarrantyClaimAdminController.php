<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\WarrantyClaim;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class WarrantyClaimAdminController extends Controller
{
    /**
     * GET /admin/warranty-claims/{status?}
     *
     * List all warranty claims with optional status filter.
     */
    public function index(string $status = 'all')
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        $validStatuses = ['all', 'pending', 'approved', 'rejected'];
        if (!in_array($status, $validStatuses)) {
            $status = 'all';
        }

        return view('backend.content.warranty_claims.index', [
            'admin'  => $admin,
            'status' => $status,
        ]);
    }

    /**
     * GET /admin/warranty-claims/data/{status}
     *
     * AJAX DataTable endpoint for warranty claims.
     */
    public function claimData(string $status = 'all')
    {
        $query = WarrantyClaim::with(['order', 'product', 'user', 'vendor', 'orderProduct']);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $claims = $query->orderByDesc('created_at')->get();

        $data = $claims->map(function (WarrantyClaim $claim) {
            $daysLeft = max(0, Carbon::today()->diffInDays($claim->warranty_expires_at, false));

            return [
                'id'               => $claim->id,
                'claim_number'     => $claim->claim_number,
                'invoice_id'       => $claim->order->invoiceID ?? '—',
                'reseller_name'    => $claim->user->name ?? '—',
                'reseller_phone'   => $claim->user->phone ?? '—',
                'product_name'     => $claim->product->ProductName ?? ($claim->orderProduct->productName ?? '—'),
                'product_code'     => $claim->product->ProductSku ?? ($claim->orderProduct->productCode ?? '—'),
                'supplier_name'    => $claim->vendor->company_name ?? ($claim->vendor->contact_name ?? '—'),
                'warranty_days'    => $claim->warranty_days,
                'delivered_at'     => $claim->delivered_at->format('d M Y'),
                'expires_at'       => $claim->warranty_expires_at->format('d M Y'),
                'days_left'        => $daysLeft,
                'status'           => $claim->status,
                'reason'           => \Illuminate\Support\Str::limit($claim->reason, 50),
                'created_at'       => $claim->created_at->format('d M Y h:i A'),
            ];
        });

        return response()->json([
            'status' => 'success',
            'data'   => $data,
        ]);
    }

    /**
     * GET /admin/warranty-claims/{id}/show
     *
     * Show detailed claim view.
     */
    public function show(int $id)
    {
        $admin = Admin::where('email', Auth::guard('admin')->user()->email)->first();

        $claim = WarrantyClaim::with(['order', 'order.customer', 'order.orderproducts', 'product', 'user', 'vendor', 'orderProduct'])
            ->findOrFail($id);

        $daysLeft = max(0, Carbon::today()->diffInDays($claim->warranty_expires_at, false));

        return view('backend.content.warranty_claims.show', [
            'admin'    => $admin,
            'claim'    => $claim,
            'daysLeft' => $daysLeft,
        ]);
    }

    /**
     * POST /admin/warranty-claims/{id}/respond
     *
     * Admin approves or rejects a claim.
     */
    public function respond(Request $request, int $id)
    {
        $request->validate([
            'action'     => 'required|in:approve,reject',
            'admin_note' => 'nullable|string|max:2000',
        ]);

        $claim = WarrantyClaim::findOrFail($id);

        if ($claim->status !== 'pending') {
            return redirect()->back()->with('error', 'This claim has already been ' . $claim->status . '.');
        }

        $claim->status = $request->action === 'approve' ? 'approved' : 'rejected';
        $claim->admin_note = $request->admin_note;
        $claim->responded_at = now();
        $claim->responded_by = Auth::guard('admin')->user()->id;
        $claim->save();

        Log::info('WarrantyClaimAdmin: claim responded', [
            'claim_id'     => $claim->id,
            'claim_number' => $claim->claim_number,
            'action'       => $request->action,
            'admin_id'     => Auth::guard('admin')->user()->id,
        ]);

        $msg = $request->action === 'approve'
            ? 'Claim #' . $claim->claim_number . ' has been approved.'
            : 'Claim #' . $claim->claim_number . ' has been rejected.';

        return redirect()->route('admin.warranty-claims.show', $claim->id)
            ->with('success', $msg);
    }
}
