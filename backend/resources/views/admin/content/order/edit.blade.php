 @php
     $assignedUser = App\Models\User::find($order->user_id);
 @endphp

 <style>
     .edit-order-section {
         background: #fff;
         border: 1px solid var(--admin-border, #e2e8f0);
         border-radius: 12px;
         padding: 20px;
         margin-bottom: 16px;
     }
     .edit-order-section-title {
         font-size: 13px;
         font-weight: 600;
         color: var(--admin-text-muted, #64748b);
         text-transform: uppercase;
         letter-spacing: 0.04em;
         margin-bottom: 16px;
         padding-bottom: 10px;
         border-bottom: 1px solid var(--admin-border, #e2e8f0);
         display: flex;
         align-items: center;
         gap: 8px;
     }
     .edit-order-section-title i {
         font-size: 15px;
         color: var(--admin-accent, #6c63ff);
     }
     .info-card {
         background: var(--admin-bg, #f5f7fa);
         border: 1px solid var(--admin-border, #e2e8f0);
         border-radius: 10px;
         padding: 14px 16px;
         margin-bottom: 12px;
     }
     .info-card-label {
         font-size: 11px;
         font-weight: 600;
         color: var(--admin-text-muted, #64748b);
         text-transform: uppercase;
         letter-spacing: 0.04em;
         margin-bottom: 6px;
     }
     .info-card .info-row {
         display: flex;
         align-items: center;
         gap: 8px;
         font-size: 13px;
         color: var(--admin-text, #1e293b);
         margin-bottom: 4px;
     }
     .info-card .info-row:last-child {
         margin-bottom: 0;
     }
     .info-card .info-row i {
         font-size: 13px;
         color: var(--admin-text-muted, #64748b);
         width: 16px;
         text-align: center;
     }
     .info-card .info-row .info-label {
         font-weight: 500;
         color: var(--admin-text-muted, #64748b);
         min-width: 50px;
     }
     .info-card .info-value {
         font-weight: 600;
         color: var(--admin-text, #1e293b);
     }
     .supplier-badge {
         display: inline-flex;
         align-items: center;
         gap: 6px;
         font-size: 12px;
         font-weight: 600;
         padding: 2px 0;
     }
     .supplier-sid {
         color: var(--admin-text-muted, #64748b);
         font-size: 11px;
         font-weight: 600;
         background: #fff;
         border: 1px solid var(--admin-border, #e2e8f0);
         padding: 2px 8px;
         border-radius: 4px;
     }
     .supplier-name {
         color: var(--admin-accent, #6c63ff);
         font-weight: 600;
     }
     .edit-order-form-label {
         font-size: 12px;
         font-weight: 600;
         color: var(--admin-text-muted, #64748b);
         margin-bottom: 6px;
         text-transform: uppercase;
         letter-spacing: 0.03em;
     }
     .edit-order-form-control {
         border-radius: 8px;
         border: 1px solid var(--admin-border, #e2e8f0);
         font-size: 13px;
         padding: 8px 12px;
         transition: border-color 0.15s ease, box-shadow 0.15s ease;
         color: var(--admin-text, #1e293b);
         background: #fff;
         width: 100%;
     }
     .edit-order-form-control:focus {
         border-color: var(--admin-accent, #6c63ff);
         box-shadow: 0 0 0 3px rgba(108, 99, 255, 0.1);
         outline: none;
     }
     .products-table {
         width: 100%;
         border-collapse: separate;
         border-spacing: 0;
         font-size: 13px;
     }
     .products-table thead th {
         background: var(--admin-bg, #f5f7fa);
         border-bottom: 1px solid var(--admin-border, #e2e8f0);
         padding: 8px 12px;
         font-size: 11px;
         font-weight: 600;
         text-transform: uppercase;
         letter-spacing: 0.04em;
         color: var(--admin-text-muted, #64748b);
         white-space: nowrap;
     }
     .products-table tbody td {
         padding: 8px 12px;
         border-bottom: 1px solid #f1f5f9;
         vertical-align: middle;
         color: var(--admin-text, #1e293b);
     }
     .products-table tbody tr:last-child td {
         border-bottom: none;
     }
     .products-table tbody tr:hover td {
         background: #fafbfe;
     }
     .products-table .product-code {
         font-size: 12px;
         font-weight: 500;
         color: var(--admin-text, #1e293b);
     }
     .products-table .product-shop {
         font-size: 11px;
         color: var(--admin-accent, #6c63ff);
         font-weight: 500;
     }
     .products-table input[type="text"],
     .products-table input[type="number"] {
         border: 1px solid var(--admin-border, #e2e8f0);
         border-radius: 6px;
         padding: 4px 8px;
         font-size: 12px;
         background: #fff;
         transition: border-color 0.15s ease;
     }
     .products-table input:focus {
         border-color: var(--admin-accent, #6c63ff);
         outline: none;
         box-shadow: 0 0 0 2px rgba(108, 99, 255, 0.1);
     }
     .summary-row {
         display: flex;
         align-items: center;
         justify-content: space-between;
         padding: 6px 0;
         font-size: 13px;
     }
     .summary-row .summary-label {
         color: var(--admin-text-muted, #64748b);
         font-weight: 500;
     }
     .summary-row .summary-value {
         font-weight: 600;
         color: var(--admin-text, #1e293b);
         min-width: 100px;
         text-align: right;
     }
     .summary-row.summary-total {
         border-top: 2px solid var(--admin-border, #e2e8f0);
         margin-top: 6px;
         padding-top: 10px;
         font-size: 14px;
     }
     .summary-row.summary-total .summary-label,
     .summary-row.summary-total .summary-value {
         font-weight: 700;
         color: var(--admin-text, #1e293b);
     }
     .btn-update-order {
         width: 100%;
         padding: 12px 24px;
         background: var(--admin-primary, #2d2a5d);
         color: #fff;
         border: none;
         border-radius: 10px;
         font-size: 14px;
         font-weight: 600;
         cursor: pointer;
         transition: all 0.2s ease;
         display: flex;
         align-items: center;
         justify-content: center;
         gap: 8px;
     }
     .btn-update-order:hover {
         background: var(--admin-primary-light, #3d3a7d);
         transform: translateY(-1px);
         box-shadow: 0 4px 12px rgba(45, 42, 93, 0.3);
     }
     .old-orders-section {
         background: #fff;
         border: 1px solid var(--admin-border, #e2e8f0);
         border-radius: 12px;
         overflow: hidden;
         margin-top: 16px;
     }
     .old-orders-header {
         display: flex;
         align-items: center;
         gap: 8px;
         padding: 14px 20px;
         border-bottom: 1px solid var(--admin-border, #e2e8f0);
         font-size: 14px;
         font-weight: 600;
         color: var(--admin-text, #1e293b);
     }
     .old-orders-header i {
         color: var(--admin-accent, #6c63ff);
     }
     .product-images-strip {
         display: flex;
         gap: 8px;
         padding: 12px 0 0 0;
         flex-wrap: wrap;
     }
     .product-images-strip img {
         width: 64px;
         height: 64px;
         object-fit: cover;
         border-radius: 8px;
         border: 1px solid var(--admin-border, #e2e8f0);
     }
 </style>

 <div class="row g-3">
     {{-- LEFT COLUMN: Customer & Order Details --}}
     <div class="col-md-6">
         {{-- Customer Info Card --}}
         <div class="edit-order-section">
             <div class="edit-order-section-title">
                 <i class="bi bi-person-circle"></i> Customer Information
             </div>

             <div class="row g-2 mb-3">
                 <div class="col-12">
                     <div class="info-card">
                         <div class="d-flex justify-content-between align-items-start">
                             <div>
                                 <div class="info-card-label">Reseller / Invoice {{ $order->invoiceID }}</div>
                                 <div class="info-row">
                                     <i class="bi bi-shop"></i>
                                     <span class="info-label">Shop:</span>
                                     <span class="info-value">{{ $assignedUser?->shop_name ?? 'N/A' }}</span>
                                 </div>
                                 <div class="info-row">
                                     <i class="bi bi-person"></i>
                                     <span class="info-label">Name:</span>
                                     <span class="info-value">{{ $assignedUser?->name ?? 'N/A' }}</span>
                                 </div>
                                 <div class="info-row">
                                     <i class="bi bi-telephone"></i>
                                     <span class="info-label">Phone:</span>
                                     <span class="info-value">{{ $assignedUser?->phone ?? 'N/A' }}</span>
                                 </div>
                             </div>
                         </div>
                     </div>
                 </div>
             </div>

             {{-- Supplier Information — grouped with product codes --}}
             @php
                 $supplierGroups = collect();
                 foreach ($order->products as $p) {
                     $prod = App\Models\Product::find($p->product_id);
                     if ($prod && $prod->vendor_id) {
                         $vendor = App\Models\Vendor::find($prod->vendor_id);
                         if ($vendor) {
                             if (!$supplierGroups->has($vendor->id)) {
                                 $supplierGroups[$vendor->id] = [
                                     'vendor' => $vendor,
                                     'products' => collect(),
                                 ];
                             }
                             $supplierGroups[$vendor->id]['products']->push($p);
                         }
                     }
                 }
             @endphp
             @if($supplierGroups->count() > 0)
             <div class="info-card" style="background: #faf9ff; border-color: #eeedfa;">
                 <div class="info-card-label" style="color: var(--admin-accent, #6c63ff);">
                     <i class="bi bi-building"></i> Supplier Info
                     @if($supplierGroups->count() > 1)
                         <span style="font-size: 10px; background: var(--admin-accent, #6c63ff); color: #fff; padding: 1px 8px; border-radius: 10px; margin-left: 6px;">{{ $supplierGroups->count() }} suppliers</span>
                     @endif
                 </div>
                 @foreach($supplierGroups as $group)
                 @php $supplier = $group['vendor']; $products = $group['products']; @endphp
                 <div style="{{ !$loop->last ? 'border-bottom: 1px solid #eeedfa; padding-bottom: 8px; margin-bottom: 8px;' : '' }}">
                     <a href="{{ route('admin.vendors.autologin', $supplier->id) }}" target="_blank" class="supplier-badge" style="text-decoration: none; cursor: pointer;" title="Open {{ $supplier->company_name }}'s panel">
                         <span class="supplier-sid">SID-{{ str_pad($supplier->id, 5, '0', STR_PAD_LEFT) }}</span>
                         <span class="supplier-name">{{ $supplier->company_name }}</span>
                         <i class="bi bi-box-arrow-up-right" style="font-size: 10px; margin-left: 4px; opacity: 0.6;"></i>
                     </a>
                     @if($supplier->contact_phone)
                     <div class="info-row" style="margin-top: 2px;">
                         <i class="bi bi-telephone" style="font-size: 11px;"></i>
                         <span style="font-size: 12px;">{{ $supplier->contact_phone }}</span>
                     </div>
                     @endif
                     <div class="info-row" style="margin-top: 4px;">
                         <i class="bi bi-box-seam" style="font-size: 11px; color: var(--admin-accent, #6c63ff);"></i>
                         <span style="font-size: 12px; color: var(--admin-accent, #6c63ff); font-weight: 500;">
                             {{ $products->pluck('productCode')->implode(', ') }}
                         </span>
                     </div>
                 </div>
                 @endforeach
             </div>
             @endif

             {{-- Order Group Info (multi-supplier checkout) --}}
             @if($order->order_group_id)
             @php
                 $relatedOrders = DB::table('orders')
                     ->select('orders.id', 'orders.invoiceID', 'orders.status', 'orders.subTotal', 'orders.deliveryCharge')
                     ->where('order_group_id', $order->order_group_id)
                     ->where('id', '!=', $order->id)
                     ->get();
             @endphp
             <div class="info-card" style="background: #fff8f0; border-color: #ffe0b2;">
                 <div class="info-card-label" style="color: #e65100;">
                     <i class="bi bi-link-45deg"></i> Order Group
                     <span style="font-size: 10px; background: #e65100; color: #fff; padding: 1px 8px; border-radius: 10px; margin-left: 6px;">{{ $order->order_group_id }}</span>
                 </div>
                 <div style="font-size: 12px; color: #795548; margin-bottom: 6px;">
                     This order is part of a multi-supplier checkout ({{ ($order->shop_count ?? 1) }} suppliers). Wallet was charged once for total delivery.
                 </div>
                 @if($relatedOrders->count() > 0)
                 <div style="font-size: 12px;">
                     <span style="font-weight: 600; color: #795548;">Related orders:</span>
                     @foreach($relatedOrders as $rel)
                     <a href="javascript:void(0)" onclick="$('#OrderEditModal .modal-body').load('{{ url('admin_order/orderedit/' . $rel->id) }}')"
                        style="display: inline-flex; align-items: center; gap: 3px; padding: 2px 8px; margin: 2px; background: #fff3e0; border: 1px solid #ffe0b2; border-radius: 6px; color: #e65100; text-decoration: none; font-size: 11px;">
                         <i class="bi bi-receipt" style="font-size: 10px;"></i>
                         {{ $rel->invoiceID }}
                         <span style="font-size: 9px; color: #795548;">({{ $rel->status }})</span>
                     </a>
                     @endforeach
                 </div>
                 @endif
             </div>
             @endif

             {{-- Customer Form Fields --}}
             <div class="row g-3" style="margin-top: 16px;">
                 <div class="col-lg-6" hidden>
                     <div class="form-group" id="storenamepart">
                         <label for="storeID" class="edit-order-form-label">Store Name</label>
                         <select id="storeID" class="edit-order-form-control" disabled>
                             <option value="1">{{ env('APP_NAME') }}</option>
                         </select>
                     </div>
                 </div>
                 <div class="col-lg-6" hidden>
                     <label for="invoiceID" class="edit-order-form-label">Invoice Number</label>
                     <input type="text" readonly class="edit-order-form-control" style="cursor: not-allowed; background: var(--admin-bg);"
                         id="invoiceID" value="{{ $order->invoiceID }}">
                 </div>
             </div>

             <div class="row g-3">
                 <div class="col-lg-6">
                     <label for="customerName" class="edit-order-form-label">Customer Name</label>
                     <input type="text" class="edit-order-form-control" id="customerName"
                         value="{{ $order->customerName }}">
                 </div>
                 @if (Auth::guard('admin')->user()?->role == 0)
                     <div class="col-lg-6">
                         <label for="customerPhone" class="edit-order-form-label">Customer Phone</label>
                         <input type="text" class="edit-order-form-control" id="customerPhone"
                             value="{{ $order->customerPhone }}">
                     </div>
                 @else
                     <div class="col-lg-6">
                         <label for="customerPhone" class="edit-order-form-label">Customer Phone</label>
                         <input type="text" class="edit-order-form-control" id="customerPhone"
                             value="{{ $order->customerPhone }}">
                     </div>
                 @endif
             </div>

             <div class="row g-3 mt-0">
                 <div class="col-lg-12">
                     <label for="customerAddress" class="edit-order-form-label">Customer Address</label>
                     <textarea class="edit-order-form-control" placeholder="Customer Address" id="customerAddress" rows="2">{{ $order->customerAddress }}</textarea>
                 </div>
             </div>
         </div>

         {{-- Shipping & Tracking Section --}}
         <div class="edit-order-section">
             <div class="edit-order-section-title">
                 <i class="bi bi-truck"></i> Shipping & Tracking
             </div>

             <div class="row g-3">
                 <div class="col-lg-12">
                     <div class="form-group" id="courierdatatbl">
                         <label for="courierID" class="edit-order-form-label">Courier</label>
                         <select id="courierID" class="edit-order-form-control">
                             <option value="{{ $order->courier_id }}">{{ $order->courierName }}</option>
                         </select>
                         <?php
                         use App\Models\Courier;
                         $couriers = Courier::all();
                         ?>
                         <script>
                             var couriers = <?php echo json_encode($couriers); ?>;
                         </script>
                     </div>
                 </div>
                 <div class="col-lg-6 hasCity">
                     <div class="form-group" id="citydatatbl">
                         <label for="cityID" class="edit-order-form-label">City</label>
                         <select id="cityID" type="text" class="edit-order-form-control">
                             <option value="{{ $order->city_id }}">{{ $order->cityName }}</option>
                         </select>
                     </div>
                 </div>
                 <div class="col-lg-6 hasZone">
                     <div class="form-group" id="xonedatatbl">
                         <label for="zoneID" class="edit-order-form-label">Zone</label>
                         <select id="zoneID" type="text" class="edit-order-form-control">
                             <option value="{{ $order->zone_id }}">{{ $order->zoneName }}</option>
                         </select>
                     </div>
                 </div>
                 <div class="col-lg-12">
                     <label for="customerNote" class="edit-order-form-label">Customer Notes</label>
                     <textarea class="edit-order-form-control" placeholder="Customer Notes" id="customerNote" rows="2">{{ $order->customerNote }}</textarea>
                 </div>
                 <div class="col-lg-6">
                     <label for="trackingLink" class="edit-order-form-label">Tracking Link</label>
                     <input type="text" class="edit-order-form-control"
                         id="trackingLink" value="{{ $order->trackingLink ?? '' }}" placeholder="Enter tracking URL">
                 </div>
                 <div class="col-lg-6">
                     <label for="parcelID" class="edit-order-form-label">Parcel ID</label>
                     <input type="text" class="edit-order-form-control"
                         id="parcelID" placeholder="Enter Parcel ID" value="{{ $order->parcel_id ?? '' }}">
                 </div>
                 <div class="col-lg-12">
                    <label for="cancel_comment" class="edit-order-form-label">Cancel Notes</label>
                    <textarea class="edit-order-form-control" placeholder="Cancel Notes" id="cancel_comment" rows="2">{{ $order->cancel_comment ?? '' }}</textarea>
                 </div>
             </div>
         </div>

         {{-- Hidden date fields --}}
         <div class="row" hidden>
             <div class="col-lg-4">
                 <label for="orderDate" class="edit-order-form-label">Order Date</label>
                 <input type="text" class="edit-order-form-control datepicker" value="{{ $order->orderDate }}" id="orderDate">
             </div>
             @if ($order->deliveryDate)
                 <div class="col-lg-4">
                     <label for="deliveryDate" class="edit-order-form-label">Delivery Date</label>
                     <input type="text" class="edit-order-form-control datepicker" id="deliveryDate" value="{{ $order->deliveryDate }}">
                 </div>
             @endif
             @if ($order->completeDate)
                 <div class="col-lg-4">
                     <label for="completeDate" class="edit-order-form-label">Complete Date</label>
                     <input type="text" class="edit-order-form-control datepicker" id="completeDate" value="{{ $order->completeDate }}">
                 </div>
             @endif
         </div>
     </div>

     {{-- RIGHT COLUMN: Products & Payment --}}
     <div class="col-md-6">
         {{-- Products Section --}}
         <div class="edit-order-section">
             <div class="edit-order-section-title">
                 <i class="bi bi-box-seam"></i> Products
             </div>

             <div style="overflow-x: auto;">
                 <table id="productTable" class="products-table">
                     <thead>
                         <tr>
                             <th>Color</th>
                             <th>Size</th>
                             <th>Code</th>
                             <th>Product</th>
                             <th>Qty</th>
                             <th>Price</th>
                             <th></th>
                         </tr>
                     </thead>
                     <tbody>
                         @foreach ($order->products as $product)
                             <tr>
                                 <td style="display: none"><input type="text" class="productID"
                                         style="width:80px;" value="{{ $product->product_id }}"></td>
                                 <td><input type="text" name="color" id="ProductColor"
                                             value="{{ $product->color }}" style="max-width: 60px;"></td>
                                 <td><input type="text" name="size" id="ProductSize"
                                             value="{{ $product->size }}" style="max-width: 45px;"></td>
                               @php
                                   $productModel = App\Models\Product::find($product->product_id);
                                   $shop = $productModel ? App\Models\Admin::find($productModel->shop_id) : null;
                               @endphp
                                 <td>
                                     <span class="productCode">{{ $product->productCode }}</span>
                                     @if(optional($shop)->name)
                                         <br><span class="product-shop">({{ $shop->name }})</span>
                                     @endif
                                 </td>
                                 <td><span class="productName">{{ $product->productName }}</span></td>
                                 <td><input type="number" class="productQuantity" style="width:65px;"
                                         value="{{ $product->quantity }}"></td>
                                 <td><input type="number" class="productPrice" style="width:80px;" value="{{ $product->productPrice }}"></td>
                                 <td><button class="btn btn-sm btn-outline-danger delete-btn" style="border-radius: 6px; padding: 4px 8px;"><i class="fa fa-trash" style="font-size: 11px;"></i></button></td>
                             </tr>
                         @endforeach
                     </tbody>
                     <tfoot>
                         <tr>
                             <td colspan="8" style="padding: 8px 12px;">
                                 <select id="productID" type="text" style="width: 100%;" class="edit-order-form-control">
                                     <option value="">Select a Product</option>
                                 </select>
                             </td>
                         </tr>
                     </tfoot>
                 </table>
             </div>

             {{-- Product Images --}}
             @php
                 $hasImages = false;
                 foreach ($order->products as $product) {
                     $pm = App\Models\Product::find($product->product_id);
                     if (optional($pm)->ProductImage) { $hasImages = true; break; }
                 }
             @endphp
             @if($hasImages)
             <div class="product-images-strip">
                 @foreach ($order->products as $product)
                     @php
                         $productModel = App\Models\Product::find($product->product_id);
                         $imgUrl = optional($productModel)->ProductImage;
                     @endphp
                     @if($imgUrl)
                         <img src="{{ str_starts_with($imgUrl, 'http') ? $imgUrl : asset($imgUrl) }}" alt="Product">
                     @endif
                 @endforeach
             </div>
             @endif
         </div>

         {{-- Payment & Summary Section --}}
         <div class="edit-order-section">
             <div class="edit-order-section-title">
                 <i class="bi bi-credit-card"></i> Payment & Summary
             </div>

             <div class="row g-3">
                 <div class="col-md-6">
                     <label class="edit-order-form-label">Payment Method</label>
                     <div id="paymntidname">
                         <select id="paymentTypeID" class="edit-order-form-control select2">
                             <option value="{{ $order->payment_type_id }}">{{ $order->paymentTypeName }}</option>
                         </select>
                     </div>

                     <div class="paymentID mt-2" id="paymentIDname">
                         <select id="paymentID" class="edit-order-form-control" style="width: 100%;">
                             <option value="{{ $order->payment_id }}">{{ $order->paymentNumber }}</option>
                         </select>
                     </div>

                     <div class="paymentAgentNumber mt-2">
                         <input type="text" class="edit-order-form-control" id="paymentAgentNumber"
                             placeholder="Enter Bkash Agent Number" value="{{ $order->paymentAgentNumber }}">
                     </div>

                     <div class="mt-2">
                         <label class="edit-order-form-label">Discount</label>
                         <input type="text" value="{{ $order->discountCharge }}" class="edit-order-form-control"
                             id="discountCharge">
                     </div>

                     <div hidden>
                         <label class="edit-order-form-label">Memo Number</label>
                         <input type="text" class="edit-order-form-control" id="memo"
                             placeholder="Enter Memo Number"
                             @if ($order->memo) value="{{ $order->memo }}" @endif>
                     </div>
                 </div>

                 <div class="col-md-6">
                     <div style="background: var(--admin-bg, #f5f7fa); border-radius: 10px; padding: 16px; border: 1px solid var(--admin-border, #e2e8f0);">
                         <div class="summary-row">
                             <span class="summary-label">Resell Price</span>
                             <span class="summary-value">{{ number_format($order->subTotal - $order->profit, 2) }}</span>
                         </div>
                         <div class="summary-row">
                             <span class="summary-label">Seller Profit</span>
                             <span class="summary-value">{{ number_format($order->profit, 2) }}</span>
                         </div>
                         <div class="summary-row">
                             <span class="summary-label">Order Bonus</span>
                             <span class="summary-value">{{ number_format($order->order_bonus ?? 0, 2) }}</span>
                         </div>
                         <div class="summary-row">
                             <span class="summary-label">Sub Total</span>
                             <span class="summary-value" id="subtotal">{{ number_format($order->subTotal + $order->profit, 2) }}</span>
                             <input type="hidden" id="orderProfit" value="{{ $order->profit }}">
                             <input type="hidden" id="advanceDelivery" value="{{ $order->advance_delivery }}">
                         </div>
                         <div class="summary-row">
                             <span class="summary-label">Delivery</span>
                             <span class="summary-value">
                                 <input type="text" class="edit-order-form-control" value="{{ $order->deliveryCharge }}"
                                     id="deliveryCharge" style="width: 100px; text-align: right; padding: 4px 8px; font-size: 13px;">
                             </span>
                         </div>
                         @if(($order->shop_count ?? 1) > 1 && $order->order_group_id)
                         <div class="summary-row" style="padding: 2px 0;">
                             <span></span>
                             <span style="font-size: 11px; color: var(--admin-text-muted, #64748b); text-align: right;">
                                 (1 of {{ $order->shop_count }} supplier orders)
                             </span>
                         </div>
                         @endif
                         <div class="summary-row">
                             <span class="summary-label">Paid</span>
                             <span class="summary-value paymentAmount">
                                 <input type="text" value="{{ $order->paymentAmount }}" class="edit-order-form-control"
                                     id="paymentAmount" style="width: 100px; text-align: right; padding: 4px 8px; font-size: 13px;">
                             </span>
                         </div>
                         <div class="summary-row summary-total">
                             <span class="summary-label">Total Due</span>
                             <span class="summary-value" id="total" style="color: var(--admin-primary, #2d2a5d); font-size: 16px;">0</span>
                         </div>
                     </div>
                 </div>
             </div>

             <div class="mt-3">
                 <button type="button" id="btn-update" value="{{ $order->id }}" class="btn-update-order">
                     <i class="bi bi-check2-circle"></i> Update Order
                 </button>
             </div>
         </div>
     </div>

     {{-- OLD ORDERS SECTION --}}
     <div class="col-md-12">
         <div class="old-orders-section">
             <div class="old-orders-header">
                 <i class="bi bi-clock-history"></i> Previous Orders
             </div>
             <div style="padding: 0;">
                 <table id="oldOrderTable" style="width: 100% !important;" data-id="{{ $order->id }}"
                     class="products-table">
                     <thead>
                         <tr>
                             <th>Invoice</th>
                             <th>User</th>
                             <th>Products</th>
                             <th>Total</th>
                             <th>Status</th>
                             <th>Reseller</th>
                         </tr>
                     </thead>
                     <tbody>
                     </tbody>
                 </table>
             </div>
         </div>
     </div>
 </div>
