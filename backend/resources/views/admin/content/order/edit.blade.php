 @php
     $assignedUser = App\Models\User::find($order->user_id);
 @endphp

 <div class="row">
     <div class="col-md-6">
         <div class="card">

             <div class="card-body">
                 <div class="row">
                     <div class="col-md-5">
                         <div class="mb-4">
                             <strong style="margin-bottom:0px">Customer (Invoice: {{ $order->invoiceID }})</strong>
                         </div>
                     </div>
                     <div class="col-md-7">
                         <div class="p-1 card card-body">
                             <strong style="margin-bottom:0px;font-size: 12px;">Shop: {{ $assignedUser?->shop_name ?? 'N/A' }} <br>Name: {{ $assignedUser?->name ?? 'N/A' }} <br> Phone: {{ $assignedUser?->phone ?? 'N/A' }}</strong>
                         </div>
                      </div>
                  </div>

                 {{-- Supplier Information --}}
                 @php
                     $suppliers = collect();
                     foreach ($order->products as $p) {
                         $prod = App\Models\Product::find($p->product_id);
                         if ($prod && $prod->vendor_id) {
                             $vendor = App\Models\Vendor::find($prod->vendor_id);
                             if ($vendor && !$suppliers->contains('id', $vendor->id)) {
                                 $suppliers->push($vendor);
                             }
                         }
                     }
                 @endphp
                 @if($suppliers->count() > 0)
                 <div class="row mt-2">
                     <div class="col-12">
                         <div class="card card-body p-2" style="background: #f8f9fa; border: 1px solid #e9ecef;">
                             <strong style="font-size: 13px; margin-bottom: 4px;">Supplier Info</strong>
                             @foreach($suppliers as $supplier)
                             <div style="font-size: 12px; {{ !$loop->last ? 'border-bottom: 1px solid #dee2e6; padding-bottom: 4px; margin-bottom: 4px;' : '' }}">
                                 <span style="color: #613EEA; font-weight: 600;">{{ $supplier->company_name }}</span><br>
                                 {{ $supplier->contact_name ?? '' }}
                                 @if($supplier->contact_phone) | {{ $supplier->contact_phone }} @endif
                                 @if($supplier->contact_email) | {{ $supplier->contact_email }} @endif
                             </div>
                             @endforeach
                         </div>
                     </div>
                 </div>
                 @endif
                 <div class="row">
                     <div class="col-lg-6" hidden>
                         <div class="form-group" id="storenamepart">
                             <label for="storeID">Store Name</label><br>
                             <select id="storeID" class="form-control" disabled>
                                 <option value="1">{{ env('APP_NAME') }}</option>
                             </select>
                         </div>
                     </div>
                     <div class="col-lg-6" hidden>
                         <div class="form-group">
                             <label for="invoiceID">Invoice Number</label>
                             <input type="text" readonly class="form-control" style="cursor: not-allowed;"
                                 id="invoiceID" value="{{ $order->invoiceID }}">
                         </div>
                     </div>
                 </div>
                 <div class="row">
                     <div class="col-lg-6">
                         <div class="form-group">
                             <label for="customerName">Customer Name</label>
                             <input type="text" class="form-control" id="customerName"
                                 value="{{ $order->customerName }}">
                         </div>
                     </div>
                     @if (Auth::guard('admin')->user()?->role == 0)
                         <div class="col-lg-6">
                             <div class="form-group">
                                 <label for="customerPhone">Customer Phone</label>
                                 <input type="text" class="form-control" id="customerPhone"
                                     value="{{ $order->customerPhone }}" >
                             </div>
                         </div>
                     @else
                         <div class="col-lg-6">
                             <div class="form-group">
                                 <label for="customerPhone">Customer Phone</label>
                                 <input type="text" class="form-control" id="customerPhone"
                                     value="{{ $order->customerPhone }}">
                             </div>
                         </div>
                     @endif
                 </div>
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="form-group">
                             <label for="customerAddress">Customer Address</label>
                             <textarea name="" class="form-control" placeholder="Customer Address" id="customerAddress" rows="2">{{ $order->customerAddress }}</textarea>
                         </div>
                     </div>
                 </div>
                 <div class="mt-3 row">
                     <div class="mb-1 col-lg-12">
                         <div class="form-group" id="courierdatatbl">
                             <label for="courierID">Courier Name</label><br>
                             <select id="courierID" class="form-control">
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
                     <div class="mb-1 col-lg-12 hasCity">
                         <div class="form-group" id="citydatatbl">
                             <label for="cityID">City Name</label><br>
                             <select id="cityID" type="text" class="form-control">
                                 <option value="{{ $order->city_id }}">{{ $order->cityName }}</option>
                             </select>
                         </div>
                     </div>
                     <div class="mb-1 col-lg-12 hasZone">
                         <div class="form-group" id="xonedatatbl">
                             <label for="zoneID">Zone Name</label><br>
                             <select id="zoneID" type="text" class="form-control">
                                 <option value="{{ $order->zone_id }}">{{ $order->zoneName }}</option>
                             </select>
                         </div>
                     </div>

                     <div class="mb-4 col-lg-12">
                         <div class="form-group">
                             <label for="customerNote">Customer Notes</label>
                             <textarea name="" class="form-control" placeholder="Customer Notes" id="customerNote" rows="2">{{ $order->customerNote }}</textarea>
                         </div>
                     </div>
                     <br>
                     <br>
                     <div class="col-lg-12">
                         <div class="form-group">
                             <label for="trackingLink">Courier Tracking Link</label>
                             <input type="text" class="form-control"
                                 id="trackingLink" value="{{ $order->trackingLink ?? '' }}">
                         </div>
                     </div>
                     <div class="mt-2 col-lg-12">
                         <div class="form-group">
                             <label for="parcelID">Parcel ID</label>
                             <input type="text" class="form-control"
                                 id="parcelID" placeholder="Enter Parcel ID after courier assignment" value="{{ $order->parcel_id ?? '' }}">
                         </div>
                     </div>
                     <div class="mt-4 col-lg-12">
                        <div class="form-group">
                            <label for="customerNote">Cancel Notes</label>
                            <textarea name="" class="form-control" placeholder="Cancel Notes" id="cancel_comment" rows="2">{{ $order->cancel_comment ?? '' }}</textarea>
                        </div>
                    </div>
                 </div>


                 <div class="row" hidden>
                     <div class="col-lg-4">
                         <div class="form-group">
                             <label for="orderDate">Order Date</label>
                             <input type="text" class="form-control datepicker" value="{{ $order->orderDate }}"
                                 id="orderDate">
                         </div>
                     </div>
                     @if ($order->deliveryDate)
                         <div class="col-lg-4">
                             <div class="form-group">
                                 <label for="deliveryDate">Delivery Date</label>
                                 <input type="text" class="form-control datepicker" id="deliveryDate"
                                     value="{{ $order->deliveryDate }}">
                             </div>
                         </div>
                     @endif
                     @if ($order->completeDate)
                         <div class="col-lg-4">
                             <div class="form-group">
                                 <label for="completeDate">Complete Date</label>
                                 <input type="text" class="form-control datepicker" id="completeDate"
                                     value="{{ $order->completeDate }}">
                             </div>
                         </div>
                     @endif



                 </div>
                 <br>
             </div>
         </div>
     </div>

     <div class="col-md-6">
         <div class="card">
             <div class="card-body">
                 <strong style="margin-bottom:10px">Products</strong>
                 <table id="productTable" style="width: 100% !important;" class="table table-bordered table-striped">
                     <thead>
                         <tr>
                             <th>Color</th>
                             <th>Size</th>
                             <th>Code</th>
                             <th>Product Name</th>
                             <th>Quantity</th>
                             <th>Price</th>
                             <th></th>
                         </tr>
                     </thead>
                     <tbody>
                         @foreach ($order->products as $product)
                             <tr>
                                 <td style="display: none"><input type="text" class="productID"
                                         style="width:80px;" value="{{ $product->product_id }}"></td>
                                 <td><span class="Color"> <input type="text" name="color" id="ProductColor"
                                             value="{{ $product->color }}" style="    max-width: 60px;"> </span>
                                 </td>
                                 <td><span class="Size"><input type="text" name="size" id="ProductSize"
                                             value="{{ $product->size }}" style="    max-width: 40px;"> </span>
                                 </td>
                               @php
    $productModel = App\Models\Product::find($product->product_id);
    $shop = $productModel ? App\Models\Admin::find($productModel->shop_id) : null;
@endphp
                                 <td><span class="productCode">{{ $product->productCode }}</span><br><span style="color:red">{{ optional($shop)->name ? '(' . $shop->name . ')' : '' }}</span></td>
                                 <td><span class="productName">{{ $product->productName }}</span></td>
                                 <td><input type="number" class="productQuantity form-control" style="width:80px;"
                                         value="{{ $product->quantity }}"></td>
                                 <td> <input type="number" id="productPrice" class="form-control" style="width:80px;" value="{{ $product->productPrice }}"></td>
                                 <td><button class="btn btn-sm btn-danger delete-btn"><i class="fa fa-trash"></i></button></td>
                             </tr>
                         @endforeach
                     </tbody>
                     <tfoot>
                         <tr>
                             <td colspan="7">
                                 <select id="productID" type="text" style="width: 100%;" class="form-control">
                                     <option value="">Select Product</option>
                                 </select>
                             </td>
                         </tr>
                     </tfoot>

                 </table>
                 <br>

                 <div class="row">
                     <div class="col-md-5">
                         <div class="mb-2 form-group" id="paymntidname">
                             <label>Payment Method</label> <br>
                             <select id="paymentTypeID" class="form-control select2">
                                 <option value="{{ $order->payment_type_id }}">{{ $order->paymentTypeName }}
                                 </option>
                             </select>
                         </div>

                         <div class="mb-2 form-group paymentID" id="paymentIDname">
                             <select id="paymentID" class="mb-2 form-control" style="width: 100%;">
                                 <option value="{{ $order->payment_id }}">{{ $order->paymentNumber }}</option>
                             </select>

                         </div>
                         <div class="form-group paymentAgentNumber">
                             <input type="text" class="form-control" id="paymentAgentNumber"
                                 placeholder="Enter Bkash Agent Number" value="{{ $order->paymentAgentNumber }}">
                         </div>

                         <div class="mb-2 form-group">
                             <label for="fname"
                                 class="text-right control-label col-form-label">Discount</label>
                                 <input type="text" value="{{ $order->discountCharge }}" class="form-control"
                                     id="discountCharge">
                         </div>

                         <div class="form-group hide" hidden>
                             <label>Memo Number</label>
                             <input type="text" class="form-control" id="memo"
                                 placeholder="Enter Memo Number"
                                 @if ($order->memo) value="{{ $order->memo }}"
                                @else @endif>
                         </div>
                     </div>
                     <div class="mb-4 col-md-7">
                         <div class="mb-2 form-group row">
                             <label for="fname" class="text-right col-sm-4 control-label col-form-label">Resell Price</label>
                             <div class="col-sm-8">
                                 <span class="form-control" style="cursor: not-allowed;">{{ number_format($order->subTotal - $order->profit, 2) }}</span>
                             </div>
                         </div>
                         <div class="mb-2 form-group row">
                             <label for="fname" class="text-right col-sm-4 control-label col-form-label">Seller Profit</label>
                             <div class="col-sm-8">
                                 <span class="form-control" style="cursor: not-allowed;">{{ number_format($order->profit, 2) }}</span>
                             </div>
                         </div>
                         <div class="mb-2 form-group row">
                             <label for="fname" class="text-right col-sm-4 control-label col-form-label">Order Bonus</label>
                             <div class="col-sm-8">
                                 <span class="form-control" style="cursor: not-allowed;">{{ number_format($order->order_bonus ?? 0, 2) }}</span>
                             </div>
                         </div>
                         <div class="mb-2 form-group row">
                             <label for="fname" class="text-right col-sm-4 control-label col-form-label">Sub
                                 Total</label>
                             <div class="col-sm-8">
                                 <span class="form-control" id="subtotal"
                                     style="cursor: not-allowed;">{{ number_format($order->subTotal, 2) }}</span>
                             </div>
                         </div>
                         <div class="mb-2 form-group row">
                             <label for="fname"
                                 class="text-right col-sm-4 control-label col-form-label">Delivery</label>
                             <div class="col-sm-8">
                                 <input type="text" class="form-control" value="{{ $order->deliveryCharge }}"
                                     id="deliveryCharge">
                             </div>
                         </div>

                         <div class="mb-2 form-group row paymentAmount">
                             <label for="fname"
                                 class="text-right col-sm-4 control-label col-form-label">Paid</label>
                             <div class="col-sm-8">
                                 <input type="text" value="{{ $order->paymentAmount }}" class="form-control"
                                     id="paymentAmount">
                             </div>
                         </div>

                         <div class="form-group row">
                             <label for="fname"
                                 class="text-right col-sm-4 control-label col-form-label">Total Due</label>
                             <div class="col-sm-8">
                                 <span class="form-control" id="total" style="cursor: not-allowed;">0</span>
                             </div>
                         </div>

                     </div>
                     <br>
                         <button type="button" id="btn-update" value="{{ $order->id }}"
                         class="mt-4 btn btn-block btn-primary" style="width: 100%;"><i
                             class="fa fa-save"></i> Update Order</button>

  @foreach ($order->products as $product)
    @php
        $productModel = App\Models\Product::find($product->product_id);
        $imgUrl = optional($productModel)->ProductImage;
    @endphp

    @if($imgUrl)
        <img src="{{ str_starts_with($imgUrl, 'http') ? $imgUrl : asset($imgUrl) }}" style="width:100px;margin-top:10px;">
    @endif
@endforeach
                 </div>
             </div>

         </div>
     </div>

     <div class="col-md-12" style="margin-top: 20px;">
            <div class="card">
                <div class="card-header">
                    <strong>Old Order</strong>
                </div>
                <div class="card-body">
                    <table id="oldOrderTable" style="width: 100% !important;" data-id="{{ $order->id }}"
                        class="table table-bordered table-striped">
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
