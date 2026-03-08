@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Policy Menu
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Policy Menu</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Policy Menu List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainPolicymenu" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Policy Menu
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="policymenuinfo" width="100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Text</th>
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

    {{-- create policy menu modal --}}
    <div class="modal fade" id="mainPolicymenu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Policy Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddPolicymenu" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Policy Menu Name</label>
                            <input type="text" class="form-control" name="policy_menu_name" id="policy_menu_name" placeholder="Policy Menu Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Policy Text</label>
                            <input type="text" class="form-control" name="policy_text" id="policy_text" placeholder="Policy Text">
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

    {{-- edit policy menu modal --}}
    <div class="modal fade" id="editmainPolicymenu" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Policy Menu</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditPolicymenu" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Policy Menu Name</label>
                            <input type="text" class="form-control" name="policy_menu_name" id="policy_menu_name" placeholder="Policy Menu Name">
                        </div>
                        <div class="mb-3">
                            <label style="font-size: 13px; font-weight: 500; margin-bottom: 5px;">Policy Text</label>
                            <input type="text" class="form-control" name="policy_text" id="policy_text" placeholder="Policy Text">
                        </div>
                        <input type="text" name="policymenu_id" id="policymenu_id" hidden>
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

        var policymenuinfo = $('#policymenuinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.policymenu.data') !!}',
            columns: [{
                    data: 'policy_menu_name',
                },
                {
                    data: 'policy_text'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="policymenustatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="policymenustatusBtn" data-id="' +
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


        //add policymenu

        $('#AddPolicymenu').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: '{{ route('admin.policymenus.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#policy_menu_name').val('');
                    $('#policy_text').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    policymenuinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit policymenu
        $(document).on('click', '#editPolicymenuBtn', function() {
            let policymenuId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'policymenus/' + policymenuId + '/edit',

                success: function(data) {
                    $('#EditPolicymenu').find('#policy_menu_name').val(data
                        .policy_menu_name);
                    $('#EditPolicymenu').find('#policy_text').val(data
                        .policy_text);
                    $('#EditPolicymenu').find('#policymenu_id').val(data.id);

                    $('#EditPolicymenu').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update policymenu
        $('#EditPolicymenu').submit(function(e) {
            e.preventDefault();
            let policymenuId = $('#policymenu_id').val();

            $.ajax({
                type: 'POST',
                url: 'policymenu/' + policymenuId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditPolicymenu').find('#policy_menu_name').val('');
                    $('#EditPolicymenu').find('#policy_text').val('');
                    $('#previmg').html('');

                    swal({
                        title: "Policymenu update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    policymenuinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        // delete policymenu

        $(document).on('click', '#deletePolicymenuBtn', function() {
            let policymenuId = $(this).data('id');
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
                            url: 'policymenus/' + policymenuId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Policymenu has been deleted!", {
                                    icon: "success",
                                });
                                policymenuinfo.ajax.reload();
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

        $(document).on('click', '#policymenustatusBtn', function() {
            let policymenuId = $(this).data('id');
            let policymenuStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'policymenu/status',
                data: {
                    policymenu_id: policymenuId,
                    status: policymenuStatus,
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
                    policymenuinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
