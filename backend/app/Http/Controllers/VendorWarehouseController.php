<?php

namespace App\Http\Controllers;

use App\Models\VendorWarehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendorWarehouseController extends Controller
{
    private function getVendor()
    {
        $user = Auth::user();
        return $user ? $user->vendor : null;
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/warehouses                                             */
    /* ------------------------------------------------------------------ */

    public function index()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $warehouses = $vendor->warehouses()->orderBy('is_default', 'desc')->orderBy('name')->get();

        return response()->json([
            'status' => true,
            'data'   => ['warehouses' => $warehouses],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST /vendor/warehouses                                            */
    /* ------------------------------------------------------------------ */

    public function store(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'label'          => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'is_default'     => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $data['vendor_id'] = $vendor->id;

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            $vendor->warehouses()->update(['is_default' => false]);
        }

        // If this is the first warehouse, make it default
        if ($vendor->warehouses()->count() === 0) {
            $data['is_default'] = true;
        }

        $warehouse = VendorWarehouse::create($data);

        return response()->json([
            'status'  => true,
            'message' => 'Warehouse created',
            'data'    => ['warehouse' => $warehouse],
        ], 201);
    }

    /* ------------------------------------------------------------------ */
    /*  PUT /vendor/warehouses/{id}                                        */
    /* ------------------------------------------------------------------ */

    public function update(Request $request, $id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $warehouse = $vendor->warehouses()->find($id);
        if (!$warehouse) {
            return response()->json(['status' => false, 'message' => 'Warehouse not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'           => 'sometimes|required|string|max:255',
            'label'          => 'nullable|string|max:255',
            'country'        => 'nullable|string|max:100',
            'state'          => 'nullable|string|max:100',
            'city'           => 'nullable|string|max:100',
            'postcode'       => 'nullable|string|max:20',
            'address_line_1' => 'nullable|string|max:255',
            'address_line_2' => 'nullable|string|max:255',
            'is_default'     => 'nullable|boolean',
            'is_active'      => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // If setting as default, unset other defaults
        if (!empty($data['is_default'])) {
            $vendor->warehouses()->where('id', '!=', $id)->update(['is_default' => false]);
        }

        $warehouse->update($data);

        return response()->json([
            'status'  => true,
            'message' => 'Warehouse updated',
            'data'    => ['warehouse' => $warehouse->fresh()],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  DELETE /vendor/warehouses/{id}                                     */
    /* ------------------------------------------------------------------ */

    public function destroy($id)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $warehouse = $vendor->warehouses()->find($id);
        if (!$warehouse) {
            return response()->json(['status' => false, 'message' => 'Warehouse not found'], 404);
        }

        // Prevent deleting if stock is allocated
        $hasStock = \App\Models\Stock::where('warehouse_id', $id)->where('stock', '>', 0)->exists();
        if ($hasStock) {
            return response()->json(['status' => false, 'message' => 'Cannot delete warehouse with allocated stock. Please transfer stock first.'], 422);
        }

        $warehouse->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Warehouse deleted',
        ]);
    }
}
