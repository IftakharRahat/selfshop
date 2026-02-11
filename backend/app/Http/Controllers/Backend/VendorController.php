<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /**
     * List vendors for admin (Blade view). JSON via ?format=json.
     * GET /admin/vendors (auth.admin)
     */
    public function index(Request $request)
    {
        $vendors = Vendor::with('user')
            ->orderByDesc('created_at')
            ->paginate(30);

        if ($request->wantsJson() || $request->get('format') === 'json') {
            return response()->json([
                'status' => true,
                'data' => $vendors,
            ]);
        }

        return view('backend.content.vendors.index', compact('vendors'));
    }

    /**
     * Approve a vendor and activate its user account.
     * POST /admin/vendors/{vendor}/approve
     */
    public function approve(Vendor $vendor)
    {
        $vendor->status = 'approved';
        $vendor->approved_at = now();
        $vendor->save();

        $user = User::find($vendor->user_id);
        if ($user) {
            $user->status = 'Active';
            $user->is_verified_wholesaler = true;
            $user->save();
        }

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Vendor approved']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Vendor approved.');
    }

    /**
     * Reject a vendor application.
     * POST /admin/vendors/{vendor}/reject
     */
    public function reject(Request $request, Vendor $vendor)
    {
        $vendor->status = 'rejected';
        $vendor->rejected_at = now();
        $vendor->notes = $request->input('reason');
        $vendor->save();

        if (request()->wantsJson()) {
            return response()->json(['status' => true, 'message' => 'Vendor rejected']);
        }
        return redirect()->route('admin.vendors.index')->with('message', 'Vendor rejected.');
    }
}

