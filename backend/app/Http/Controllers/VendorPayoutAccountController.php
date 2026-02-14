<?php

namespace App\Http\Controllers;

use App\Models\VendorPayoutAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendorPayoutAccountController extends Controller
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
     * GET /vendor/payout-accounts
     */
    public function index()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $accounts = $vendor->payoutAccounts()->orderBy('is_default', 'desc')->get();

        return response()->json([
            'status' => true,
            'data' => ['payout_accounts' => $accounts],
        ]);
    }

    /**
     * POST /vendor/payout-accounts
     */
    public function store(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'channel_type' => 'required|string|in:bank,mobile_wallet,other',
            'provider_name' => 'nullable|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'routing_number' => 'nullable|string|max:100',
            'is_default' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['vendor_id'] = $vendor->id;
        $data['is_active'] = true;

        if (!empty($data['is_default'])) {
            $vendor->payoutAccounts()->update(['is_default' => false]);
        }

        $account = VendorPayoutAccount::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Payout account added',
            'data' => ['payout_account' => $account],
        ], 201);
    }

    /**
     * PUT /vendor/payout-accounts/{id}
     */
    public function update(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $account = $vendor->payoutAccounts()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'channel_type' => 'sometimes|string|in:bank,mobile_wallet,other',
            'provider_name' => 'nullable|string|max:255',
            'account_name' => 'sometimes|string|max:255',
            'account_number' => 'sometimes|string|max:255',
            'routing_number' => 'nullable|string|max:100',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        if (!empty($data['is_default'])) {
            $vendor->payoutAccounts()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $account->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Payout account updated',
            'data' => ['payout_account' => $account->fresh()],
        ]);
    }

    /**
     * DELETE /vendor/payout-accounts/{id}
     */
    public function destroy($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $account = $vendor->payoutAccounts()->findOrFail($id);
        $account->delete();

        return response()->json([
            'status' => true,
            'message' => 'Payout account deleted',
        ]);
    }
}
