@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Product Varient
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Product Variants</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Variant List of — {{$product->ProductName}}</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainCategory" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Variant
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="categoryinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Title</th>
                            <th>Color Name</th>
                            <th>Color</th>
                            <th>Quantity</th>
                            <th>1/Price</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($varients as $ind=>$varient)
                            <tr>
                                <td>{{$ind+1}}</td>
                                <td>{{$varient->title}}</td>
                                <td>{{ $varient->color_name ?? '-' }}</td>
                                <td>
                                    @if(!empty($varient->color_code))
                                        <span
                                            style="display:inline-block;width:20px;height:20px;border-radius:999px;border:1px solid #cbd5e1;background:{{ $varient->color_code }};"
                                            title="{{ $varient->color_code }}"
                                        ></span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>{{$varient->qty}}</td>
                                <td>{{$varient->price}}</td>
                                <td>
                                    @if($varient->status == 'Active')
                                        <span class="badge bg-success">{{$varient->status}}</span>
                                    @else
                                        <span class="badge bg-warning text-dark">{{$varient->status}}</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="#" type="button" onclick="manageSizes('{{$varient->id}}', '{{$varient->title}}')" class="btn btn-info btn-sm text-white" title="Manage Sizes" data-bs-toggle="tooltip"><i class="bi bi-list-nested"></i> Sizes</a>
                                    <a href="#" type="button" id="editCategoryBtn" data-id="{{$varient->id}}" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainCategory"><i class="bi bi-pencil-square"></i></a>
                                    <a href="#" type="button" id="deleteCategoryBtn" data-id="{{$varient->id}}" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>
                                </td>
                            </tr>
                        @empty
                           <tr>
                                <td colspan="8" class="text-center py-4">No data found</td>
                           </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create variant modal --}}
    <div class="modal fade" id="mainCategory" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Variant of — {{$product->ProductName}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddCategory" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3 form-floating">
                            <input type="text" class="form-control" name="title" id="title" placeholder="Title" required>
                            <label for="floatingInput">Title</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="qty" id="qty" placeholder="Quantity" required>
                            <label for="floatingInput">Quantity</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="text" class="form-control" name="color_name" id="color_name" placeholder="Color Name">
                            <label for="floatingInput">Color Name (Optional)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input
                                type="text"
                                class="form-control"
                                name="color_code"
                                id="color_code"
                                placeholder="#FF0000"
                                pattern="^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$"
                            >
                            <label for="color_code">Color Code (Optional, e.g. #FF0000)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="price" id="price" placeholder="Price" required>
                            <label for="floatingInput">Price</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="extra_delivery_charge" id="extra_delivery_charge" placeholder="Extra Delivery Charge Per Pics" required>
                            <label for="floatingInput">Extra Delivery Charge Per Pics</label>
                        </div>
                        <input type="text" name="product_id" id="product_id" value="{{$product->id}}" hidden>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- End popup Modal-->

    {{-- edit variant modal --}}
    <div class="modal fade" id="editmainCategory" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Variant of — {{$product->ProductName}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditCategory" enctype="multipart/form-data">
                        @csrf
                         <div class="mb-3 form-floating">
                            <input type="text" class="form-control" name="title" id="title" placeholder="Title" required>
                            <label for="floatingInput">Title</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="qty" id="qty" placeholder="Quantity" required>
                            <label for="floatingInput">Quantity</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="text" class="form-control" name="color_name" id="color_name" placeholder="Color Name">
                            <label for="floatingInput">Color Name (Optional)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input
                                type="text"
                                class="form-control"
                                name="color_code"
                                id="color_code"
                                placeholder="#FF0000"
                                pattern="^#?[A-Fa-f0-9]{3}([A-Fa-f0-9]{3})?$"
                            >
                            <label for="color_code">Color Code (Optional, e.g. #FF0000)</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="price" id="price" placeholder="Price" required>
                            <label for="floatingInput">Price</label>
                        </div>
                        <div class="mb-3 form-floating">
                            <input type="number" class="form-control" name="extra_delivery_charge" id="extra_delivery_charge" placeholder="Extra Delivery Charge Per Pics" required>
                            <label for="floatingInput">Extra Delivery Charge Per Pics</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        <input type="text" name="product_id" id="product_id" value="{{$product->id}}" hidden>
                        <input type="text" name="varient_id" id="varient_id" hidden>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div><!-- End popup Modal-->

    {{-- manage sizes modal --}}
    <div class="modal fade" id="manageSizesModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Manage Sizes for <span id="sizesVariantTitle" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body bg-light">
                    <!-- Add Size Form -->
                    <div class="card mb-3 shadow-sm border-0">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">Add New Size</h6>
                            <form id="AddSizeForm" class="row g-2 align-items-center">
                                <input type="hidden" id="manage_variant_id" name="variant_id">
                                <div class="col-md-3">
                                    <input type="text" class="form-control form-control-sm" id="new_size_name" name="size_name" placeholder="Size (e.g. XL, 42)" required>
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control form-control-sm" id="new_size_price" name="price" step="0.01" placeholder="Price (Optional)">
                                </div>
                                <div class="col-md-3">
                                    <input type="number" class="form-control form-control-sm" id="new_size_qty" name="qty" placeholder="Quantity" value="0" required>
                                </div>
                                <div class="col-md-3 text-end">
                                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Add Size</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Sizes List -->
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="bg-gray-50 border-bottom">
                                        <tr>
                                            <th>Size</th>
                                            <th>Overridden Price</th>
                                            <th>Stock Qty</th>
                                            <th>Status</th>
                                            <th class="text-end">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sizesTableBody">
                                        <tr><td colspan="5" class="text-center py-3 text-muted">Loading sizes...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div><!-- End manage sizes modal-->
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        //add category
        $('#AddCategory').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.varients.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#category_name').val('');
                    $('#category_icon').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    location.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit category
        $(document).on('click', '#editCategoryBtn', function() {
            let categoryId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: "{{url('admin/varients')}}"+'/'+  categoryId + '/edit',

                success: function(data) {
                    $('#EditCategory').find('#title').val(data.title);
                    $('#EditCategory').find('#qty').val(data.qty);
                    $('#EditCategory').find('#color_name').val(data.color_name || '');
                    $('#EditCategory').find('#color_code').val(data.color_code || '');
                    $('#EditCategory').find('#price').val(data.price);
                    $('#EditCategory').find('#extra_delivery_charge').val(data.extra_delivery_charge);
                    $('#EditCategory').find('#status').val(data.status);
                    $('#EditCategory').find('#varient_id').val(data.id);
                    $('#EditCategory').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update category
        $('#EditCategory').submit(function(e) {
            e.preventDefault();
            let categoryId = $('#varient_id').val();

            $.ajax({
                type: 'POST',
                url: "{{url('admin/varient')}}"+"/"+  categoryId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {

                    swal({
                        title: "Varient update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    location.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete category
        $(document).on('click', '#deleteCategoryBtn', function() {
            let categoryId = $(this).data('id');
            swal({
                    title: "Are you sure?",
                    text: "Once deleted, you will not be able to recover this !",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                .then((willDelete) => {
                    if (willDelete) {
                        $.ajax({
                            type: 'DELETE',
                            url: '../../varients/' + categoryId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Varient has been deleted!", {
                                    icon: "success",
                                });
                                location.reload();
                            },
                            error: function(error) {
                                console.log('error');
                            }

                        });


                    } else {
                        swal("Your data is safe!");
                    }
                });

        });

        // status update
        $(document).on('click', '#categorystatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'category/status',
                data: {
                    category_id: categoryId,
                    status: categoryStatus,
                    '_token': token
                },

                success: function(data) {
                    swal({
                        title: "Status updated !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    categoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        // front status update
        $(document).on('click', '#categoryfrontstatusBtn', function() {
            let categoryId = $(this).data('id');
            let categoryFrontStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'category/status',
                data: {
                    category_id: categoryId,
                    front_status: categoryFrontStatus,
                    '_token': token
                },

                success: function(data) {
                    swal({
                        title: "Status updated !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    categoryinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        // Add size form submit
        $('#AddSizeForm').submit(function(e) {
            e.preventDefault();
            let variantId = $('#manage_variant_id').val();
            let data = {
                size_name: $('#new_size_name').val(),
                price: $('#new_size_price').val() || null,
                qty: $('#new_size_qty').val(),
                status: 'Active',
                _token: token
            };

            $.ajax({
                type: 'POST',
                url: "{{url('admin/varients')}}/" + variantId + "/sizes",
                data: data,
                success: function(res) {
                    $('#new_size_name').val('');
                    $('#new_size_price').val('');
                    $('#new_size_qty').val('0');
                    loadSizes(variantId);
                },
                error: function(error) {
                    console.log('error', error);
                    swal("Error", "Could not add size", "error");
                }
            });
        });
    });

    function manageSizes(variantId, title) {
        $('#sizesVariantTitle').text(title);
        $('#manage_variant_id').val(variantId);
        $('#manageSizesModal').modal('show');
        loadSizes(variantId);
    }

    function loadSizes(variantId) {
        $('#sizesTableBody').html('<tr><td colspan="5" class="text-center py-3 text-muted spinner-border mx-auto block"></td></tr>');
        $.ajax({
            type: 'GET',
            url: "{{url('admin/varients')}}/" + variantId + "/sizes",
            success: function(data) {
                let html = '';
                if(data.length === 0) {
                    html = '<tr><td colspan="5" class="text-center py-3 text-muted">No sizes found for this variant.</td></tr>';
                } else {
                    data.forEach(function(size) {
                        html += `
                            <tr>
                                <td class="align-middle fw-medium">${size.size_name}</td>
                                <td class="align-middle">${size.price ? '৳' + size.price : '<span class="text-muted text-sm">Takes var price</span>'}</td>
                                <td class="align-middle px-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <input type="number" class="form-control form-control-sm text-center" style="width: 70px" value="${size.qty}" id="update_qty_${size.id}">
                                        <button class="btn btn-sm btn-outline-success py-1 px-2" onclick="updateSizeQty(${variantId}, ${size.id}, '${size.size_name}', ${size.price || null}, '${size.status}')"><i class="bi bi-check2"></i></button>
                                    </div>
                                </td>
                                <td class="align-middle"><span class="badge ${size.status == 'Active' ? 'bg-success' : 'bg-warning'}">${size.status}</span></td>
                                <td class="align-middle text-end">
                                    <button class="btn btn-sm btn-danger py-1" onclick="deleteSize(${variantId}, ${size.id})"><i class="bi bi-trash"></i></button>
                                </td>
                            </tr>
                        `;
                    });
                }
                $('#sizesTableBody').html(html);
            },
            error: function(error) {
                $('#sizesTableBody').html('<tr><td colspan="5" class="text-center py-3 text-danger">Error loading sizes.</td></tr>');
            }
        });
    }

    function updateSizeQty(variantId, sizeId, sizeName, price, status) {
        let newQty = $('#update_qty_' + sizeId).val();
        $.ajax({
            type: 'PUT',
            url: "{{url('admin/varients')}}/" + variantId + "/sizes/" + sizeId,
            data: {
                size_name: sizeName,
                price: price,
                qty: newQty,
                status: status,
                _token: $("input[name='_token']").val()
            },
            success: function(res) {
                // Flash success briefly
                let btn = $('#update_qty_' + sizeId).next('button');
                btn.removeClass('btn-outline-success').addClass('btn-success text-white');
                setTimeout(() => btn.removeClass('btn-success text-white').addClass('btn-outline-success'), 1000);
            },
            error: function(error) {
                swal("Error", "Could not update quantity", "error");
            }
        });
    }

    function deleteSize(variantId, sizeId) {
        swal({
            title: "Are you sure?",
            text: "Delete this size?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        })
        .then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    type: 'DELETE',
                    url: "{{url('admin/varients')}}/" + variantId + "/sizes/" + sizeId,
                    data: {
                        _token: $("input[name='_token']").val()
                    },
                    success: function(res) {
                        loadSizes(variantId);
                    },
                    error: function(error) {
                        swal("Error", "Could not delete size", "error");
                    }
                });
            }
        });
    }
</script>

@endsection
