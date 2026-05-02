<?php

namespace App\Http\Controllers;

use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShippingAddressController extends Controller
{
    /**
     * List all saved shipping addresses for the authenticated user.
     */
    public function index()
    {
        $addresses = ShippingAddress::where('user_id', Auth::id())
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => true,
            'data' => $addresses,
        ], 200);
    }

    /**
     * Store a new shipping address.
     */
    public function store(Request $request)
    {
        $request->validate([
            'label' => 'nullable|string|max:100',
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:500',
            'phone' => 'required|string|max:20',
            'is_default' => 'nullable|boolean',
            'city_id' => 'nullable|integer',
            'zone_id' => 'nullable|integer',
            'area_id' => 'nullable|integer',
        ]);

        $userId = Auth::id();

        // If setting as default, unset other defaults
        if ($request->is_default) {
            ShippingAddress::where('user_id', $userId)
                ->update(['is_default' => false]);
        }

        $address = ShippingAddress::create([
            'user_id' => $userId,
            'label' => $request->label,
            'name' => $request->name,
            'address' => $request->address,
            'phone' => $request->phone,
            'is_default' => $request->is_default ?? false,
            'city_id' => $request->city_id,
            'zone_id' => $request->zone_id,
            'area_id' => $request->area_id,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Address saved successfully',
            'data' => $address,
        ], 201);
    }

    /**
     * Update an existing shipping address.
     */
    public function update(Request $request, $id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $request->validate([
            'label' => 'nullable|string|max:100',
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'is_default' => 'nullable|boolean',
        ]);

        // If setting as default, unset other defaults
        if ($request->is_default) {
            ShippingAddress::where('user_id', Auth::id())
                ->where('id', '!=', $id)
                ->update(['is_default' => false]);
        }

        $address->update($request->only(['label', 'name', 'address', 'phone', 'is_default', 'city_id', 'zone_id', 'area_id']));

        return response()->json([
            'status' => true,
            'message' => 'Address updated successfully',
            'data' => $address,
        ], 200);
    }

    /**
     * Delete a shipping address.
     */
    public function destroy($id)
    {
        $address = ShippingAddress::where('id', $id)
            ->where('user_id', Auth::id())
            ->first();

        if (!$address) {
            return response()->json([
                'status' => false,
                'message' => 'Address not found',
            ], 404);
        }

        $address->delete();

        return response()->json([
            'status' => true,
            'message' => 'Address deleted successfully',
        ], 200);
    }
}
