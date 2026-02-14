<?php

namespace App\Http\Controllers;

use App\Models\VendorShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendorShippingMethodController extends Controller
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
     * List vendor's shipping methods.
     */
    public function index()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $methods = $vendor->shippingMethods()->orderBy('is_default', 'desc')->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'data' => ['shipping_methods' => $methods],
        ]);
    }

    /**
     * Create a shipping method.
     */
    public function store(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'required|in:flat,weight,zone',
            'rate' => 'required|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0',
            'per_kg_rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        $data['vendor_id'] = $vendor->id;

        if (!empty($data['is_default'])) {
            $vendor->shippingMethods()->update(['is_default' => false]);
        }

        $method = VendorShippingMethod::create($data);

        return response()->json([
            'status' => true,
            'message' => 'Shipping method created',
            'data' => ['shipping_method' => $method],
        ], 201);
    }

    /**
     * Update a shipping method.
     */
    public function update(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $method = $vendor->shippingMethods()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|in:flat,weight,zone',
            'rate' => 'sometimes|numeric|min:0',
            'min_order_amount' => 'nullable|numeric|min:0',
            'max_order_amount' => 'nullable|numeric|min:0',
            'per_kg_rate' => 'nullable|numeric|min:0',
            'description' => 'nullable|string|max:500',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();
        if (!empty($data['is_default'])) {
            $vendor->shippingMethods()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $method->update($data);

        return response()->json([
            'status' => true,
            'message' => 'Shipping method updated',
            'data' => ['shipping_method' => $method->fresh()],
        ]);
    }

    /**
     * Delete a shipping method.
     */
    public function destroy($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $method = $vendor->shippingMethods()->findOrFail($id);
        $method->delete();

        return response()->json([
            'status' => true,
            'message' => 'Shipping method deleted',
        ]);
    }
}
