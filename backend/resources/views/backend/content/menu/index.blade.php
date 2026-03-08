@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Menu
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Menu</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Menu List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainMenu" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Menu
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="menuinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Banner</th>
                            <th>Name</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>

                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create menu modal --}}
    <div class="modal fade" id="mainMenu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddMenu" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Menu Name</label>
                            <input type="text" class="form-control" name="menu_name" id="menu_name" placeholder="Menu Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Banner Image</label>
                            <input class="form-control" name="menu_banner" id="menu_banner" type="file">
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- edit menu modal --}}
    <div class="modal fade" id="editmainMenu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditMenu" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Menu Name</label>
                            <input type="text" class="form-control" name="menu_name" id="menu_name" placeholder="Menu Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Banner Image</label>
                            <input class="form-control" name="menu_banner" id="menu_banner" type="file">
                        </div>
                        <input type="text" name="menu_id" id="menu_id" hidden>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Current Banner</label>
                            <div id="previmg"></div>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="submit" name="btn" data-bs-dismiss="modal" class="btn btn-outline-secondary">Close</button>
                            <button type="submit" name="btn" class="btn" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <input type="hidden" name="_token" value="{{ csrf_token() }}" />
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var menuinfo = $('#menuinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.menu.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'menu_banner',
                    name: 'menu_banner',
                    render: function(data, type, full, meta) {
                        var imgSrc = data && data.startsWith('http') ? data : '../' + data;
                        return "<img src='" + imgSrc + "' height=\"40\" alt='No Image'/>";
                    }
                },
                {
                    data: 'menu_name'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="menustatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="menustatusBtn" data-id="' +
                                data.id + '" >Inactive</button>';
                        }


                    }
                },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                },

            ]
        });


        //add menu

        $('#AddMenu').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.menus.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#menu_name').val('');
                    $('#menu_banner').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    menuinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit menu
        $(document).on('click', '#editMenuBtn', function() {
            let menuId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'menus/' + menuId + '/edit',

                success: function(data) {
                    $('#EditMenu').find('#menu_name').val(data
                        .menu_name);
                    $('#EditMenu').find('#menu_id').val(data.id);

                    $('#previmg').html('');
                    var prevSrc = data.menu_banner && data.menu_banner.startsWith('http') ? data.menu_banner : '../' + data.menu_banner;
                    $('#previmg').append(`
                        <img  src="` + prevSrc + `" alt = "" style="height: 80px" />
                    `);

                    $('#EditMenu').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update menu
        $('#EditMenu').submit(function(e) {
            e.preventDefault();
            let menuId = $('#menu_id').val();

            $.ajax({
                type: 'POST',
                url: 'menu/' + menuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditMenu').find('#menu_name').val('');
                    $('#previmg').html('');

                    swal({
                        title: "Menu update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    menuinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete menu

        $(document).on('click', '#deleteMenuBtn', function() {
            let menuId = $(this).data('id');
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
                            url: 'menus/' + menuId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Menu has been deleted!", {
                                    icon: "success",
                                });
                                menuinfo.ajax.reload();
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

        $(document).on('click', '#menustatusBtn', function() {
            let menuId = $(this).data('id');
            let menuStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'menu/status',
                data: {
                    menu_id: menuId,
                    status: menuStatus,
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
                    menuinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
