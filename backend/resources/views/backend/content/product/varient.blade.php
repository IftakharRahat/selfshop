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
                                    <a href="#" type="button" id="editCategoryBtn" data-id="{{$varient->id}}" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#editmainCategory"><i class="bi bi-pencil-square"></i></a>
                                    <a href="#" type="button" id="deleteCategoryBtn" data-id="{{$varient->id}}" class="btn btn-danger btn-sm"><i class="bi bi-archive"></i></a>
                                </td>
                            </tr>
                        @empty
                           <tr>
                                <td colspan="6" class="text-center py-4">No data found</td>
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

    });
</script>

@endsection
