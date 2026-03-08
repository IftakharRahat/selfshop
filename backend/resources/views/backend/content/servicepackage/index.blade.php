@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Service Package
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Service Packages</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Service Package List</h6>
            <div class="admin-card-actions">
                <a type="button" data-bs-toggle="modal" data-bs-target="#mainServicepackage" class="btn btn-sm" style="background: var(--admin-primary, #2d2a5d); color: #fff; border-radius: 6px;">
                    <i class="bi bi-plus-lg me-1"></i> Create Package
                </a>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0" id="servicepackageinfo" width="100%">
                    <thead>
                        <tr>
                            <th>SL</th>
                            <th>Package</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- create service package modal --}}
    <div class="modal fade" id="mainServicepackage" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Create New Service Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="AddServicepackage" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" class="form-control" name="servicepackage_name" id="servicepackage_name" placeholder="Service Package Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Package Text</label>
                            <input type="text" class="form-control" name="package_text" id="package_text" placeholder="Package Text">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="roles" id="role">
                                <option value="">Select Roles</option>
                                @forelse ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @empty
                                @endforelse
                            </select>
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

    {{-- edit service package modal --}}
    <div class="modal fade" id="editmainServicepackage" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" style="font-weight: 600;">Edit Service Package</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form name="form" id="EditServicepackage" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Package Name</label>
                            <input type="text" class="form-control" name="servicepackage_name" id="servicepackage_name" placeholder="Package Name">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Package Text</label>
                            <input type="text" class="form-control" name="package_text" id="package_text" placeholder="Package Text">
                        </div>
                        <input type="text" name="servicepackage_id" id="servicepackage_id" hidden>
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <select class="form-select" name="roles" id="role" disabled>
                                <option value="">Select Roles</option>
                                @forelse ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @empty
                                @endforelse
                            </select>
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

        var servicepackageinfo = $('#servicepackageinfo').DataTable({
            order: [[0, 'desc']],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.servicepackage.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'servicepackage_name'
                }, {
                    data: 'role'
                },
                {
                    "data": null,
                    render: function(data) {
                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="servicepackagestatusBtn" data-id="' + data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="servicepackagestatusBtn" data-id="' + data.id + '" >Inactive</button>';
                        }
                    }
                },
                { data: 'action', name: 'action', orderable: false, searchable: false },
            ]
        });

        $('#AddServicepackage').submit(function(e) {
            e.preventDefault();
            $.ajax({
                type: 'POST', url: '{{ route('admin.servicepackages.store') }}',
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    if (data == 'exist') {
                        swal({ icon: 'error', title: 'Can not process !', text: 'Already have a package with this role', buttons: true, buttons: "Thanks" });
                    } else {
                        $('#servicepackage_name').val(''); $('#package_text').val(''); $('#roles').val('');
                        swal({ title: "Success!", icon: "success" });
                        servicepackageinfo.ajax.reload();
                    }
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#editServicepackageBtn', function() {
            let servicepackageId = $(this).data('id');
            $.ajax({
                type: 'GET', url: 'servicepackages/' + servicepackageId + '/edit',
                success: function(data) {
                    $('#EditServicepackage').find('#servicepackage_name').val(data.servicepackage_name);
                    $('#EditServicepackage').find('#package_text').val(data.package_text);
                    $('#EditServicepackage').find('#servicepackage_id').val(data.id);
                    $('#EditServicepackage').find('#role').val(data.roles);
                    $('#EditServicepackage').attr('data-id', data.id);
                },
                error: function(error) { console.log('error'); }
            });
        });

        $('#EditServicepackage').submit(function(e) {
            e.preventDefault();
            let servicepackageId = $('#servicepackage_id').val();
            $.ajax({
                type: 'POST', url: 'servicepackage/' + servicepackageId,
                processData: false, contentType: false, data: new FormData(this),
                success: function(data) {
                    $('#EditServicepackage').find('#servicepackage_name').val('');
                    $('#EditServicepackage').find('#package_text').val('');
                    $('#EditServicepackage').find('#servicepackage_id').val('');
                    $('#EditServicepackage').find('#role').val('');
                    swal({ title: "Servicepackage update successfully !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    servicepackageinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });

        $(document).on('click', '#servicepackagestatusBtn', function() {
            let servicepackageId = $(this).data('id');
            let servicepackageStatus = $(this).data('status');
            $.ajax({
                type: 'PUT', url: 'servicepackage/status',
                data: { servicepackage_id: servicepackageId, status: servicepackageStatus, '_token': token },
                success: function(data) {
                    swal({ title: "Status updated !", icon: "success", showCancelButton: true, focusConfirm: false, confirmButtonColor: "#DD6B55", confirmButtonText: "Yes", cancelButtonText: "No" });
                    servicepackageinfo.ajax.reload();
                },
                error: function(error) { console.log('error'); }
            });
        });
    });
</script>

@endsection
