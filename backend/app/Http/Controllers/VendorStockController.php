<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorStockController extends Controller
{
    /* ------------------------------------------------------------------ */
    /*  Helper: get authenticated vendor                                   */
    /* ------------------------------------------------------------------ */

    private function getVendor()
    {
        $user = Auth::user();
        return $user ? $user->vendor : null;
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/inventory                                              */
    /*  Paginated inventory list with search & status filter               */
    /* ------------------------------------------------------------------ */

    public function index(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $query = Product::where('vendor_id', $vendor->id);

        // Search by name or SKU
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('ProductName', 'like', "%{$search}%")
                  ->orWhere('ProductSku', 'like', "%{$search}%");
            });
        }

        // Filter by stock status
        $status = $request->input('stock_status');
        if ($status === 'in_stock') {
            $query->whereColumn('qty', '>', 'low_stock')->where('qty', '>', 0);
        } elseif ($status === 'low_stock') {
            $query->where('qty', '>', 0)->whereColumn('qty', '<=', 'low_stock');
        } elseif ($status === 'out_of_stock') {
            $query->where('qty', '<=', 0);
        }

        $products = $query->select([
            'id', 'ProductName', 'ProductSku', 'ProductImage',
            'qty', 'low_stock', 'show_stock', 'show_stock_text', 'status',
        ])->orderBy('qty', 'asc')->paginate($request->input('per_page', 20));

        // Summary counts
        $total    = Product::where('vendor_id', $vendor->id)->count();
        $inStock  = Product::where('vendor_id', $vendor->id)->whereColumn('qty', '>', 'low_stock')->where('qty', '>', 0)->count();
        $lowStock = Product::where('vendor_id', $vendor->id)->where('qty', '>', 0)->whereColumn('qty', '<=', 'low_stock')->count();
        $outStock = Product::where('vendor_id', $vendor->id)->where('qty', '<=', 0)->count();

        return response()->json([
            'status' => true,
            'data'   => [
                'summary' => [
                    'total'        => $total,
                    'in_stock'     => $inStock,
                    'low_stock'    => $lowStock,
                    'out_of_stock' => $outStock,
                ],
                'products'   => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/inventory/{productId}                                   */
    /*  Single product stock detail + movement history                     */
    /* ------------------------------------------------------------------ */

    public function show($productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('id', $productId)
            ->where('vendor_id', $vendor->id)
            ->select(['id', 'ProductName', 'ProductSku', 'ProductImage', 'qty', 'low_stock', 'show_stock', 'show_stock_text'])
            ->first();

        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $movements = StockMovement::where('product_id', $productId)
            ->with('warehouse:id,name', 'creator:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Per-warehouse breakdown
        $warehouseStock = Stock::where('product_id', $productId)
            ->whereNotNull('warehouse_id')
            ->with('warehouse:id,name')
            ->get(['id', 'warehouse_id', 'stock']);

        return response()->json([
            'status' => true,
            'data'   => [
                'product'         => $product,
                'movements'       => $movements->items(),
                'warehouse_stock' => $warehouseStock,
                'pagination'      => [
                    'current_page' => $movements->currentPage(),
                    'last_page'    => $movements->lastPage(),
                    'per_page'     => $movements->perPage(),
                    'total'        => $movements->total(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST /vendor/inventory/{productId}/adjust                          */
    /*  Manual stock adjustment (±qty + reason)                            */
    /* ------------------------------------------------------------------ */

    public function adjust(Request $request, $productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('id', $productId)->where('vendor_id', $vendor->id)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'quantity'     => 'required|integer|not_in:0',
            'type'         => 'required|in:purchase,return,adjustment',
            'warehouse_id' => 'nullable|exists:vendor_warehouses,id',
            'note'         => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();
        $qty  = (int) $data['quantity'];

        // Prevent negative total stock
        if ($product->qty + $qty < 0) {
            return response()->json(['status' => false, 'message' => 'Insufficient stock. Current stock: ' . $product->qty], 422);
        }

        // Update product qty
        $product->qty += $qty;
        $product->save();

        // Update stocks table
        $stockRow = Stock::where('product_id', $productId)->first();
        if ($stockRow) {
            $stockRow->stock = $product->qty;
            $stockRow->save();
        }

        // Log movement
        StockMovement::create([
            'product_id'     => $productId,
            'warehouse_id'   => $data['warehouse_id'] ?? null,
            'type'           => $data['type'],
            'quantity'        => $qty,
            'note'           => $data['note'] ?? null,
            'created_by'     => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Stock adjusted successfully',
            'data'    => ['new_qty' => $product->qty],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/inventory/alerts                                        */
    /*  Products where qty <= low_stock                                    */
    /* ------------------------------------------------------------------ */

    public function alerts(Request $request)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $products = Product::where('vendor_id', $vendor->id)
            ->where(function ($q) {
                $q->whereColumn('qty', '<=', 'low_stock')
                  ->orWhere('qty', '<=', 0);
            })
            ->select(['id', 'ProductName', 'ProductSku', 'ProductImage', 'qty', 'low_stock'])
            ->orderBy('qty', 'asc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'status' => true,
            'data'   => [
                'products'   => $products->items(),
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'last_page'    => $products->lastPage(),
                    'per_page'     => $products->perPage(),
                    'total'        => $products->total(),
                ],
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/inventory/export                                        */
    /*  CSV download of current inventory                                  */
    /* ------------------------------------------------------------------ */

    public function export()
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $products = Product::where('vendor_id', $vendor->id)
            ->select(['id', 'ProductName', 'ProductSku', 'qty', 'low_stock', 'status'])
            ->get();

        return new StreamedResponse(function () use ($products) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Product Name', 'SKU', 'Current Qty', 'Low Stock Threshold', 'Status']);
            foreach ($products as $p) {
                $stockStatus = $p->qty <= 0 ? 'Out of Stock' : ($p->qty <= $p->low_stock ? 'Low Stock' : 'In Stock');
                fputcsv($handle, [$p->id, $p->ProductName, $p->ProductSku, $p->qty, $p->low_stock, $stockStatus]);
            }
            fclose($handle);
        }, 200, [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory_' . date('Y-m-d') . '.csv"',
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST /vendor/inventory/{productId}/update-threshold                 */
    /*  Update the low_stock threshold for a product                       */
    /* ------------------------------------------------------------------ */

    public function updateThreshold(Request $request, $productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('id', $productId)->where('vendor_id', $vendor->id)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'low_stock' => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $product->low_stock = $request->input('low_stock');
        $product->save();

        return response()->json([
            'status'  => true,
            'message' => 'Low stock threshold updated',
            'data'    => ['low_stock' => $product->low_stock],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  GET /vendor/inventory/{productId}/warehouses                        */
    /*  Stock per warehouse for a product                                  */
    /* ------------------------------------------------------------------ */

    public function warehouseStock($productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('id', $productId)->where('vendor_id', $vendor->id)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $warehouseStock = Stock::where('product_id', $productId)
            ->whereNotNull('warehouse_id')
            ->with('warehouse:id,name,label')
            ->get(['id', 'warehouse_id', 'stock']);

        return response()->json([
            'status' => true,
            'data'   => [
                'product_id'      => (int) $productId,
                'total_qty'       => $product->qty,
                'warehouse_stock' => $warehouseStock,
            ],
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST /vendor/inventory/{productId}/allocate                        */
    /*  Allocate stock to a warehouse                                      */
    /* ------------------------------------------------------------------ */

    public function allocate(Request $request, $productId)
    {
        $vendor = $this->getVendor();
        if (!$vendor) {
            return response()->json(['status' => false, 'message' => 'Vendor not found'], 403);
        }

        $product = Product::where('id', $productId)->where('vendor_id', $vendor->id)->first();
        if (!$product) {
            return response()->json(['status' => false, 'message' => 'Product not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'warehouse_id' => 'required|exists:vendor_warehouses,id',
            'quantity'     => 'required|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        // Verify the warehouse belongs to this vendor
        $warehouse = $vendor->warehouses()->find($request->input('warehouse_id'));
        if (!$warehouse) {
            return response()->json(['status' => false, 'message' => 'Warehouse does not belong to vendor'], 403);
        }

        $stockRow = Stock::updateOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouse->id],
            ['stock' => $request->input('quantity'), 'purchase' => 0]
        );

        // Log movement
        StockMovement::create([
            'product_id'   => $productId,
            'warehouse_id' => $warehouse->id,
            'type'         => 'transfer',
            'quantity'     => $request->input('quantity'),
            'note'         => 'Allocated to warehouse: ' . $warehouse->name,
            'created_by'   => Auth::id(),
        ]);

        return response()->json([
            'status'  => true,
            'message' => 'Stock allocated to warehouse',
            'data'    => $stockRow,
        ]);
    }

    /* ------------------------------------------------------------------ */
    /*  POST /vendor/inventory/dropship-sync (stub)                        */
    /* ------------------------------------------------------------------ */

    public function syncDropshipStock()
    {
        return response()->json([
            'status'  => false,
            'message' => 'Dropship stock sync is not yet implemented. This endpoint is reserved for future integration.',
        ], 501);
    }
}
