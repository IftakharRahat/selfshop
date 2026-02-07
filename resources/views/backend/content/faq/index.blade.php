@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Faq
@endsection
<style>
    div#roleinfo_length {
        color: red;
    }

    div#roleinfo_filter {
        color: red;
    }

    div#roleinfo_info {
        color: red;
    }
</style>

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="h-100 bg-secondary rounded p-4 pb-0">
                <div class="d-flex align-items-center justify-content-between" style="width: 50%;float:left;">
                    <h6 class="mb-0">Faq List</h6>
                </div>
                <div class="" style="width: 50%;float:left;">
                    <a type="button" data-bs-toggle="modal" data-bs-target="#mainFaq" class="btn btn-primary m-2"
                        style="float: right"> + Create Faq</a>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-12 col-xl-12">
            <div class="bg-secondary rounded h-100 p-4">
                <div class="data-tables">
                    <table class="table table-dark" id="faqinfo" width="100%" style="text-align: center;">
                        <thead class="thead-light">
                            <tr>
                                <th>SL</th>
                                <th>Question</th>
                                <th>Answer</th>
                                <th>status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- create payment icon --}}
        <div class="modal fade" id="mainFaq" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Create New Faq</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="AddFaq" enctype="multipart/form-data">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="question" id="question"
                                    placeholder="Question">
                                <label for="floatingInput">Question</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="youtube_embade" id="youtube_embade"
                                    placeholder="Youtube Embade Code">
                                <label for="floatingInput">Youtube Embade Code</label>
                            </div>
                            <div class="form-control mb-3">
                                <label for="floatingInput">Answer</label>
                                <textarea name="answer" id="answer" rows="3" class="form-control"></textarea>
                            </div>
        
                            <br>

                            <div class="form-group mt-2" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" data-bs-dismiss="modal"
                                        class="btn btn-dark btn-block" style="float: left">Close</button>
                                    <button type="submit" name="btn"
                                        class="btn btn-primary AddCourierBtn btn-block">Save</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->

        {{-- edit payment icon --}}
        <div class="modal fade" id="editmainFaq" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content bg-secondary rounded h-100">
                    <div class="modal-header">
                        <h5 class="modal-title" style="color: red;">Edit Faq</h5>
                        <button type="button" class="btn-dark btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <form name="form" id="EditFaq" enctype="multipart/form-data">
                            @csrf
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="question" id="question"
                                    placeholder="Question">
                                <label for="floatingInput">Question</label>
                            </div>
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control" name="youtube_embade" id="youtube_embade"
                                    placeholder="Youtube Embade Code">
                                <label for="floatingInput">Youtube Embade Code</label>
                            </div>
                            <div class="form-control mb-3">
                                <label for="floatingInput">Answer</label>
                                <textarea name="answer" id="answer" rows="3" class="form-control"></textarea>
                            </div>

                            <input type="text" name="faq_id" id="faq_id" hidden>

                            <br>
                            <div class="form-group mt-2" style="text-align: right">
                                <div class="submitBtnSCourse">
                                    <button type="submit" name="btn" data-bs-dismiss="modal"
                                        class="btn btn-dark btn-block" style="float: left">Close</button>
                                    <button type="submit" name="btn"
                                        class="btn btn-primary AddCourierBtn btn-block">Update</button>
                                </div>
                            </div>
                        </form>

                    </div>

                </div>
            </div>
        </div><!-- End popup Modal-->
        <input type="hidden" name="_token" value="{{ csrf_token() }}" />
    </div>
</div>

<script>
    $(document).ready(function() {
        var token = $("input[name='_token']").val();

        var faqinfo = $('#faqinfo').DataTable({
            order: [
                [0, 'desc']
            ],
            processing: true,
            serverSide: true,
            ajax: '{!! route('faq.data') !!}',
            columns: [{
                    data: 'id'
                },{
                    data: 'question'
                },{
                    data: 'answer'
                },
                {
                    "data": null,
                    render: function(data) {

                        if (data.status === 'Active') {
                            return '<button type="button" class="btn btn-success btn-sm btn-status" data-status="Inactive" id="faqstatusBtn" data-id="' +
                                data.id + '">Active</button>';
                        } else {
                            return '<button type="button" class="btn btn-warning btn-sm btn-status" data-status="Active" id="faqstatusBtn" data-id="' +
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


        //add faq

        $('#AddFaq').submit(function(e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                uploadUrl: '{{ route('faqs.store') }}',
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {

                    $('#question').val('');
                    $('#answer').val('');
                    $('#youtube_embade').val('');

                    swal({
                        title: "Success!",
                        icon: "success",
                    });
                    faqinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });

        //edit faq
        $(document).on('click', '#editFaqBtn', function() {
            let faqId = $(this).data('id');

            $.ajax({
                type: 'GET',
                url: 'faqs/' + faqId + '/edit',

                success: function(data) {
                    $('#EditFaq').find('#question').val(data.question);
                    $('#EditFaq').find('#answer').val(data.answer);
                    $('#EditFaq').find('#youtube_embade').val(data.youtube_embade);
                    $('#EditFaq').find('#faq_id').val(data.id);

                    $('#EditFaq').attr('data-id', data.id);
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });

        //update faq
        $('#EditFaq').submit(function(e) {
            e.preventDefault();
            let faqId = $('#faq_id').val();

            $.ajax({
                type: 'POST',
                url: 'faq/' + faqId,
                processData: false,
                contentType: false,
                data: new FormData(this),

                success: function(data) {
                    $('#EditFaq').find('#question').val('');
                    $('#EditFaq').find('#answer').val('');
                    $('#EditFaq').find('#youtube_embade').val('');

                    swal({
                        title: "Faq update successfully !",
                        icon: "success",
                        showCancelButton: true,
                        focusConfirm: false,
                        confirmButtonColor: "#DD6B55",
                        confirmButtonText: "Yes",
                        cancelButtonText: "No",
                    });
                    faqinfo.ajax.reload();

                },
                error: function(error) {
                    console.log('error');
                }
            });
        });


        // status update

        $(document).on('click', '#faqstatusBtn', function() {
            let faqId = $(this).data('id');
            let faqStatus = $(this).data('status');

            $.ajax({
                type: 'PUT',
                url: 'faq/status',
                data: {
                    faq_id: faqId,
                    status: faqStatus,
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
                    faqinfo.ajax.reload();
                },
                error: function(error) {
                    console.log('error');
                }

            });
        });


    });
</script>

@endsection
