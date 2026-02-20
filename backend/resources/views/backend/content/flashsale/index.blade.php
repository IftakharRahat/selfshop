@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Flash Sales
@endsection
<style>
    div#flashsaleinfo_length { color: red; }
    div#flashsaleinfo_filter { color: red; }
    div#flashsaleinfo_info { color: red; }

    /* Dropdown trigger */
    .fs-dropdown-trigger {
        display: flex; align-items: center; justify-content: space-between;
        padding: 10px 14px; border: 1px solid #ced4da; border-radius: 6px;
        color: #495057; cursor: pointer; background: #fff;
    }
    .fs-dropdown-trigger:hover { border-color: #80bdff; }
    .fs-dropdown-trigger.open { border-color: #0d6efd; border-radius: 6px 6px 0 0; }

    /* Dropdown panel */
    .fs-dropdown-panel {
        display: none; position: absolute; left: 0; right: 0;
        background: #fff; border: 1px solid #0d6efd; border-top: none;
        border-radius: 0 0 6px 6px; z-index: 100; max-height: 350px; overflow: hidden;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .fs-dropdown-panel.show { display: block; }
    .fs-dropdown-search { padding: 8px 12px; border-bottom: 1px solid #eee; }
    .fs-dropdown-search input {
        width: 100%; padding: 6px 10px; background: #f8f9fa; border: 1px solid #ced4da;
        border-radius: 4px; color: #495057; font-size: 13px; outline: none;
    }
    .fs-dropdown-search input:focus { border-color: #0d6efd; background: #fff; }
    .fs-dropdown-list { max-height: 280px; overflow-y: auto; }
    .fs-dropdown-list::-webkit-scrollbar { width: 5px; }
    .fs-dropdown-list::-webkit-scrollbar-thumb { background: #ccc; border-radius: 3px; }
    .fs-subcat-header {
        padding: 6px 12px; font-size: 11px; font-weight: 700; color: #0d6efd;
        text-transform: uppercase; letter-spacing: 0.5px; background: #f1f3f5;
        position: sticky; top: 0; border-bottom: 1px solid #e9ecef;
    }
    .fs-product-opt {
        display: flex; align-items: center; padding: 6px 12px; cursor: pointer; gap: 8px; border-bottom: 1px solid #f8f9fa;
    }
    .fs-product-opt:hover { background: #e9ecef; }
    .fs-product-opt img { width: 32px; height: 32px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; }
    .fs-product-opt .pname { font-size: 13px; color: #212529; }
    .fs-product-opt .pprice { font-size: 11px; color: #6c757d; }

    /* Current product cards */
    .fs-prod-item {
        display: flex; align-items: center; gap: 10px;
        padding: 8px 10px; margin-bottom: 6px; background: #fff;
        border: 1px solid #dee2e6; border-radius: 6px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }
    .fs-prod-item img { width: 40px; height: 40px; object-fit: cover; border-radius: 4px; border: 1px solid #dee2e6; }
    .fs-prod-item .info { flex: 1; }
    .fs-prod-item .info .name { font-size: 13px; color: #212529; font-weight: 500; }
    .fs-prod-item .info .badge-disc {
        display: inline-block; font-size: 10px; padding: 1px 6px;
        border-radius: 3px; background: #0d6efd; color: #fff; margin-top: 2px;
    }
    .fs-empty { text-align: center; padding: 20px; color: #6c757d; font-size: 13px; background: #f8f9fa; border-radius: 6px; border: 1px dashed #ced4da; }

    /* Inline discount overlay */
    .fs-discount-overlay {
        display: none; position: absolute; top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(0,0,0,0.5); z-index: 200; border-radius: 6px;
        justify-content: center; align-items: center;
    }
    .fs-discount-overlay.show { display: flex; }
    .fs-discount-box {
        background: #fff; border-radius: 8px; padding: 20px 24px; width: 320px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
    }
    .fs-discount-box h6 { color: red; margin-bottom: 4px; }
    .fs-discount-box .prod-label { font-size: 12px; color: #888; margin-bottom: 12px; }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="h-100 bg-secondary rounded p-4 pb-0">
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Flash Sale List</h6>
                </div>
                <div class="" style="width: 50%;float:left;">
                    <a type="button" data-bs-toggle="modal" data-bs-target="#createFlashSaleModal" class="btn btn-primary m-2"
                        style="float: right"> + Create Flash Sale</a>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="bg-secondary rounded h-100 p-4">
                <div class="data-tables">
                    <table class="table table-dark" id="flashsaleinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Title</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Products</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Create Flash Sale Modal --}}
        <div class="modal fade" id="createFlashSaleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Create New Flash Sale</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form name="form" id="AddFlashSale">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="title" id="create_title" placeholder="Title" required>
                                <label>Title</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="datetime-local" class="form-control" name="start_time" id="create_start_time" required>
                                <label>Start Time</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="datetime-local" class="form-control" name="end_time" id="create_end_time" required>
                                <label>End Time</label>
                            </div>
                            <div class="form-group mt-2" style="text-align: right">
                                <button type="button" data-bs-dismiss="modal" class="btn btn-dark btn-block" style="float: left">Close</button>
                                <button type="submit" class="btn btn-primary btn-block">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Edit Flash Sale Modal --}}
        <div class="modal fade" id="editFlashSaleModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Edit Flash Sale</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form name="form" id="EditFlashSale">
                            @csrf
                            <input type="hidden" name="flash_sale_id" id="edit_flash_sale_id">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="title" id="edit_title" placeholder="Title" required>
                                <label>Title</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="datetime-local" class="form-control" name="start_time" id="edit_start_time" required>
                                <label>Start Time</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="datetime-local" class="form-control" name="end_time" id="edit_end_time" required>
                                <label>End Time</label>
                            </div>
                            <div class="form-group mt-2" style="text-align: right">
                                <button type="button" data-bs-dismiss="modal" class="btn btn-dark btn-block" style="float: left">Close</button>
                                <button type="submit" class="btn btn-primary btn-block">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Manage Products Modal --}}
        <div class="modal fade" id="manageProductsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-secondary rounded h-100" style="position: relative;">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Manage Flash Sale Products</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="manage_flash_sale_id">

                        {{-- Product Dropdown --}}
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">Add Product</label>
                        <div style="position: relative;" id="fsDropdownContainer">
                            <div class="fs-dropdown-trigger" id="fsDropdownTrigger">
                                <span>Select a product to add...</span>
                                <i class="bi bi-chevron-down"></i>
                            </div>
                            <div class="fs-dropdown-panel" id="fsDropdownPanel">
                                <div class="fs-dropdown-search">
                                    <input type="text" id="fsSearchInput" placeholder="Search products...">
                                </div>
                                <div class="fs-dropdown-list" id="fsDropdownList">
                                    <div style="text-align:center;padding:16px;color:#666;">Loading...</div>
                                </div>
                            </div>
                        </div>

                        <hr style="border-color: #dee2e6; margin: 16px 0;">

                        {{-- Current Products --}}
                        <label class="form-label" style="font-size: 13px; font-weight: 600;">
                            Current Products <span id="productCount" style="color: #0d6efd;"></span>
                        </label>
                        <div id="currentProducts"></div>

                        {{-- Inline Discount Overlay --}}
                        <div class="fs-discount-overlay" id="discountOverlay">
                            <div class="fs-discount-box">
                                <h6>Set Discount %</h6>
                                <p class="prod-label" id="discountProductName"></p>
                                <div class="form-floating mb-3">
                                    <input type="number" class="form-control" id="discountInput" placeholder="Discount %" min="0" max="99" value="10">
                                    <label>Discount %</label>
                                </div>
                                <div style="display:flex; gap:8px; justify-content:flex-end;">
                                    <button type="button" class="btn btn-dark btn-sm" id="discountCancel">Cancel</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="discountConfirm">Add Product</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        console.log('FlashSale JS loaded - v3 prompt edition');
        var token = $("input[name='_token']").val();
        var pendingProductId = null;
        var pendingProductName = '';

        var flashsaleinfo = $('#flashsaleinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route("admin.flashsale.data") !!}',
            columns: [
                { data: 'id' },
                { data: 'title' },
                { data: 'start_time' },
                { data: 'end_time' },
                { data: 'product_count' },
                {
                    data: null,
                    render: function(data) {
                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm" id="flashsaleStatusBtn" data-status="Inactive" data-id="' + data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm" id="flashsaleStatusBtn" data-status="Active" data-id="' + data.id + '">Inactive</button>';
                        }
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ]
        });

        // Create Flash Sale
        $('#AddFlashSale').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST',
                url: '{{ route("admin.flashsales.store") }}',
                processData: false,
                contentType: false,
                data: new FormData(this),
                success: function(data) {
                    if (data == 'error') {
                        swal({ icon: 'error', title: 'Please fill all required fields', buttons: "OK" });
                    } else {
                        $('#create_title').val('');
                        $('#create_start_time').val('');
                        $('#create_end_time').val('');
                        $('#createFlashSaleModal').modal('hide');
                        swal({ title: "Flash Sale created!", icon: "success" });
                        flashsaleinfo.ajax.reload();
                    }
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Edit Flash Sale
        $(document).on('click', '#editFlashSaleBtn', function() {
            let id = $(this).data('id');
            $.ajax({
                type: 'GET',
                url: 'flashsales/' + id + '/edit',
                success: function(data) {
                    $('#edit_flash_sale_id').val(data.id);
                    $('#edit_title').val(data.title);
                    if (data.start_time) $('#edit_start_time').val(data.start_time.replace(' ', 'T').substring(0, 16));
                    if (data.end_time) $('#edit_end_time').val(data.end_time.replace(' ', 'T').substring(0, 16));
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Update Flash Sale
        $('#EditFlashSale').submit(function(e) {
            e.preventDefault();
            let id = $('#edit_flash_sale_id').val();
            $.ajax({
                type: 'POST',
                url: 'flashsale/' + id,
                processData: false,
                contentType: false,
                data: new FormData(this),
                success: function(data) {
                    if (data == 'error') {
                        swal({ icon: 'error', title: 'Please fill all required fields', buttons: "OK" });
                    } else {
                        $('#editFlashSaleModal').modal('hide');
                        swal({ title: "Flash Sale updated!", icon: "success" });
                        flashsaleinfo.ajax.reload();
                    }
                },
                error: function(error) { console.log('error', error); }
            });
        });

        // Delete Flash Sale
        $(document).on('click', '#deleteFlashSaleBtn', function() {
            let id = $(this).data('id');
            swal({
                title: "Are you sure?",
                text: "This will delete the flash sale and all its products!",
                icon: "warning", buttons: true, dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: 'DELETE', url: 'flashsales/' + id, data: { '_token': token },
                        success: function() { swal("Flash Sale deleted!", { icon: "success" }); flashsaleinfo.ajax.reload(); },
                        error: function() { console.log('error'); }
                    });
                }
            });
        });

        // Status Toggle
        $(document).on('click', '#flashsaleStatusBtn', function() {
            let id = $(this).data('id');
            let status = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'flashsale/status',
                data: { flash_sale_id: id, status: status, '_token': token },
                success: function() { swal({ title: "Status updated!", icon: "success" }); flashsaleinfo.ajax.reload(); },
                error: function() { console.log('error'); }
            });
        });

        // ===== PRODUCT DROPDOWN =====
        $('#fsDropdownTrigger').click(function() {
            var panel = $('#fsDropdownPanel');
            if (panel.hasClass('show')) {
                panel.removeClass('show');
                $(this).removeClass('open');
            } else {
                panel.addClass('show');
                $(this).addClass('open');
                loadAllProducts();
                setTimeout(function() { $('#fsSearchInput').focus(); }, 100);
            }
        });

        $(document).click(function(e) {
            if (!$(e.target).closest('#fsDropdownContainer').length) {
                $('#fsDropdownPanel').removeClass('show');
                $('#fsDropdownTrigger').removeClass('open');
            }
        });

        function loadAllProducts(query) {
            $.ajax({
                type: 'GET', url: 'flashsale/search-products', data: query ? { q: query } : {},
                success: function(grouped) { renderDropdownList(grouped); },
                error: function(error) { console.log('error', error); }
            });
        }

        function renderDropdownList(grouped) {
            var html = '';
            var hasItems = false;
            for (var subcat in grouped) {
                var products = grouped[subcat];
                if (products.length === 0) continue;
                hasItems = true;
                html += '<div class="fs-subcat-header">' + subcat + ' (' + products.length + ')</div>';
                products.forEach(function(p) {
                    var img = p.ViewProductImage ? '../' + p.ViewProductImage : '';
                    var price = p.ProductSalePrice || p.ProductRegularPrice || '0';
                    html += '<div class="fs-product-opt" data-pid="' + p.id + '" data-pname="' + (p.ProductName || '').replace(/"/g, '&quot;') + '">';
                    html += '<img src="' + img + '" alt="" onerror="this.style.display=\'none\'">';
                    html += '<div><div class="pname">' + p.ProductName + '</div>';
                    html += '<div class="pprice">৳' + price + '</div></div></div>';
                });
            }
            if (!hasItems) html = '<div style="text-align:center;padding:16px;color:#666;">No products found</div>';
            $('#fsDropdownList').html(html);
        }

        var searchTimer;
        $('#fsSearchInput').on('input', function() {
            var q = $(this).val();
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() { loadAllProducts(q); }, 300);
        });

        // Click product -> show discount overlay
        $(document).on('click', '.fs-product-opt', function() {
            pendingProductId = $(this).data('pid');
            pendingProductName = $(this).data('pname');
            $('#fsDropdownPanel').removeClass('show');
            $('#fsDropdownTrigger').removeClass('open');

            // Show the inline discount overlay
            $('#discountProductName').text(pendingProductName);
            $('#discountInput').val(10);
            $('#discountOverlay').addClass('show');
        });

        // Cancel discount overlay
        $('#discountCancel').click(function() {
            $('#discountOverlay').removeClass('show');
            pendingProductId = null;
            pendingProductName = '';
        });

        // Confirm discount and add product
        $('#discountConfirm').click(function() {
            var discount = parseFloat($('#discountInput').val()) || 0;
            if (discount < 0 || discount > 99) {
                swal({ icon: 'error', title: 'Discount must be between 0 and 99%' });
                return;
            }

            var flashSaleId = $('#manage_flash_sale_id').val();
            var $btn = $(this);
            $btn.prop('disabled', true).text('Adding...');

            $.ajax({
                type: 'POST',
                url: '{{ route("admin.flashsale.addproduct") }}',
                data: { '_token': token, flash_sale_id: flashSaleId, product_id: pendingProductId, discount_percentage: discount },
                success: function() {
                    $('#discountOverlay').removeClass('show');
                    $btn.prop('disabled', false).text('Add Product');
                    swal({ title: "Product added!", icon: "success" });
                    loadFlashSaleProducts(flashSaleId);
                    flashsaleinfo.ajax.reload();
                    pendingProductId = null;
                    pendingProductName = '';
                },
                error: function(err) {
                    $btn.prop('disabled', false).text('Add Product');
                    if (err.responseJSON && err.responseJSON.error) swal({ icon: 'error', title: err.responseJSON.error });
                }
            });
        });

        // Manage Products button
        $(document).on('click', '#manageProductsBtn', function() {
            var id = $(this).data('id');
            $('#manage_flash_sale_id').val(id);
            $('#fsSearchInput').val('');
            loadFlashSaleProducts(id);
        });

        function loadFlashSaleProducts(flashSaleId) {
            $.ajax({
                type: 'GET', url: 'flashsale/products/' + flashSaleId,
                success: function(products) {
                    var html = '';
                    $('#productCount').text('(' + products.length + ')');
                    if (products.length === 0) {
                        html = '<div class="fs-empty"><i class="bi bi-box-seam" style="font-size:24px;display:block;margin-bottom:6px;"></i>No products added yet</div>';
                    }
                    products.forEach(function(fsp) {
                        var p = fsp.product;
                        if (!p) return;
                        var img = p.ViewProductImage ? '../' + p.ViewProductImage : '';
                        html += '<div class="fs-prod-item">';
                        html += '<img src="' + img + '" alt="" onerror="this.style.display=\'none\'">';
                        html += '<div class="info"><div class="name">' + (p.ProductName || 'N/A') + '</div>';
                        html += '<span class="badge-disc">' + fsp.discount_percentage + '% OFF</span></div>';
                        html += '<button class="btn btn-danger btn-sm removeProductBtn" data-id="' + fsp.id + '"><i class="bi bi-trash"></i></button>';
                        html += '</div>';
                    });
                    $('#currentProducts').html(html);
                },
                error: function(error) { console.log('error', error); }
            });
        }

        // Remove Product
        $(document).on('click', '.removeProductBtn', function() {
            var id = $(this).data('id');
            var flashSaleId = $('#manage_flash_sale_id').val();
            swal({
                title: "Remove this product?", icon: "warning", buttons: true, dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    $.ajax({
                        type: 'DELETE', url: 'flashsale/remove-product/' + id, data: { '_token': token },
                        success: function() { swal("Product removed!", { icon: "success" }); loadFlashSaleProducts(flashSaleId); flashsaleinfo.ajax.reload(); },
                        error: function() { console.log('error'); }
                    });
                }
            });
        });
    });
</script>

@endsection
