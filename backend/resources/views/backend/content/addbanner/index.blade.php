@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Add Banners
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Banner List</h6>
                    <div class="admin-card-actions">
                        <a class="btn btn-primary btn-sm" href="{{ route('admin.addbanners.create') }}">
                            <i class="bi bi-plus-lg"></i> Create Banner
                        </a>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table" id="sliderinfo" width="100%">
                            <thead>
                                <tr>
                                    <th>SL</th>
                                    <th>Title</th>
                                    <th>Icon</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($addbanners as $addbanner)
                                    <tr>
                                        <td>{{ $addbanner->id }}</td>
                                        <td>{{ $addbanner->title }}</td>
                                        <td>
                                            @if($addbanner->icon)
                                                <img src="{{ asset($addbanner->icon) }}" alt=""
                                                    style="height:40px;width:60px;object-fit:cover;border-radius:6px;"
                                                    onerror="this.outerHTML='<div style=\'width:60px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px\'><i class=\'bi bi-image\'></i></div>'">
                                            @else
                                                <div style="width:60px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:16px">
                                                    <i class="bi bi-image"></i>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($addbanner->status == 'Active')
                                                <form action="{{ url('admin/addbanner/status/' . $addbanner->id) }}"
                                                    method="post" style="display:inline;">
                                                    @method('PUT')
                                                    @csrf
                                                    <input type="text" name="status" value="Inactive" hidden>
                                                    <button type="submit" class="btn btn-success btn-sm">Active</button>
                                                </form>
                                            @else
                                                <form action="{{ url('admin/addbanner/status/' . $addbanner->id) }}"
                                                    method="post" style="display:inline;">
                                                    @method('PUT')
                                                    @csrf
                                                    <input type="text" name="status" value="Active" hidden>
                                                    <button type="submit" class="btn btn-warning btn-sm">Inactive</button>
                                                </form>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.addbanners.edit', $addbanner->id) }}" type="button"
                                                class="btn btn-primary btn-sm"><i class="bi bi-pencil-square"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        // status update

        $(document).on('click', '#sliderstatusBtn', function() {
            let sliderId = $(this).data('id');
            let sliderStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'slider/status',
                data: {
                    slider_id: sliderId,
                    status: sliderStatus,
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
                    sliderinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

    });
</script>

@endsection
