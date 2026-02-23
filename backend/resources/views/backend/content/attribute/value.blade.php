@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Attribute Values
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Attribute Value List</h6>
                    <div class="admin-card-actions">
                        <a type="button" data-bs-toggle="modal" data-bs-target="#mainAttrvalue" class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-lg"></i> Create Attribute Value
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="attrvalueinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Attribute</th>
                                    <th>Value</th>
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
        </div>

        {{-- Create Attribute Value Modal --}}
        <div class="modal fade" id="mainAttrvalue" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Create Attribute Value</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">
                        <form name="form" id="AddAttrvalue" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="value" id="value" placeholder="Enter value name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attribute <span class="text-danger">*</span></label>
                                <select class="form-select" name="attribute_id" id="attribute_id">
                                    <option value="">Select Attribute</option>
                                    @forelse ($attributes as $attribute)
                                        <option value="{{ $attribute->id }}">{{ $attribute->attribute_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary btn-sm">Save</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- End Create Modal -->

        {{-- Edit Attribute Value Modal --}}
        <div class="modal fade" id="editmainAttrvalue" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content admin-modal">
                    <div class="modal-header admin-modal-header">
                        <h5 class="modal-title">Edit Attribute Value</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body admin-modal-body">
                        <form name="form" id="EditAttrvalue" enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label">Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="value" id="value"
                                    placeholder="Enter value name">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Attribute <span class="text-danger">*</span></label>
                                <select class="form-select" name="attribute_id" id="attribute_id">
                                    <option value="">Select Attribute</option>
                                    @forelse ($attributes as $attribute)
                                        <option value="{{ $attribute->id }}">{{ $attribute->attribute_name }}
                                        </option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            <input type="text" name="attrvalue_id" id="attrvalue_id" hidden>
                            <div class="admin-modal-footer">
                                <button type="button" data-bs-dismiss="modal"
                                    class="btn btn-outline-secondary btn-sm">Close</button>
                                <button type="submit" name="btn"
                                    class="btn btn-primary btn-sm">Update</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- End Edit Modal -->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var attrvalueinfo = $('#attrvalueinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('admin.attrvalue.data') !!}',
            columns: [{
                    data: 'id'
                }, {
                    data: 'attribute_name'
                },
                {
                    data: 'value'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="attrvaluestatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="attrvaluestatusBtn" data-id="' +
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


        //add attrvalue

        $('#AddAttrvalue').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                uploadUrl: '{{ route('admin.attrvalues.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {

                    $('#value').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    attrvalueinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit attrvalue
        $(document).on('click', '#editAttrvalueBtn', function() {
            let attrvalueId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'attrvalues/' + attrvalueId + '/edit',

                success: function(data) {
                    $('#EditAttrvalue').find('#attribute_id').val(data
                        .attribute_id);
                    $('#EditAttrvalue').find('#value').val(data
                        .value);
                    $('#EditAttrvalue').find('#attrvalue_id').val(data.id);

                    $('#EditAttrvalue').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update attrvalue
        $('#EditAttrvalue').submit(function(e) {
            e.preventDefault();
            let attrvalueId = $('#attrvalue_id').val();

            $.ajax({
                type: 'POST',
                url: 'attrvalue/' + attrvalueId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditAttrvalue').find('#value').val('');
                    $('#EditAttrvalue').find('#attribute_id').val('');
                    $('#EditAttrvalue').find('#attrvalue_id').val('');

                    swal({
                        title: "Attrvalue update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    attrvalueinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });


        // status update

        $(document).on('click', '#attrvaluestatusBtn', function() {
            let attrvalueId = $(this).data('id');
            let attrvalueStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'attrvalue/status',
                data: {
                    attrvalue_id: attrvalueId,
                    status: attrvalueStatus,
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
                    attrvalueinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        $(document).on('click', '#deleteAttrvalueBtn', function() {
            let attrvalueId = $(this).data('id');
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
                            url: 'attrvalues/' + attrvalueId,
                            data: {
                                '_token': token
                            },
                            success: function(data) {
                                swal("Attrvalue has been deleted!", {
                                    icon: "success",
                                });
                                attrvalueinfo.ajax.reload();
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

    });
</script>

@endsection
