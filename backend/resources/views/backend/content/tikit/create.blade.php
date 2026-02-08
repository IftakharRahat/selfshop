@extends('user.content.maincontent')

@section('usercontent')

@section('title')
    {{ env('APP_NAME') }}- Create Support Ticket
@endsection
<style>
    span.select2.select2-container.select2-container--default {
        width: 80px !important;
    }

    #titlename {
        text-align: center;
        padding: 10px;
        text-transform: uppercase;
        background: gray;
        color: white;
        border-radius: 3px;
    }

    div {
        color: black;
    }

    #accountbtn {
        width: 50%;
        padding: 10px;
        font-size: 22px;
    }

    #pb30 {
        padding-bottom: 50px;
    }

    .ptlg {
        padding-top: 74px;
    }

    @media only screen and (max-width: 600px) {
        #accountbtn {
            width: 100%;
            padding: 10px;
            font-size: 22px;
        }

        .ptlg {
            padding-top: 0px;
        }
    }
</style>
<div class="container-fluid pt-4">
    <div class="row g-4">
        <div class="col-12 col-lg-12 m-auto" id="pb30">
            <h4 id="titlename">Create New Ticket</h4>
            <div class="row">
                <div class="col-12 col-md-12">
                    <div class="card p-4">
                        <form action="{{ route('supporttikits.store') }}" method="post" enctype="multipart/form-data">
                            @csrf
                            <div class="row mb-2">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="name">Name</label>
                                        <input class="form-control" type="text" name="name"
                                            value="{{ Auth::user()->name }}" disabled>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="name">Email / Phone</label>
                                        <input class="form-control" type="text" name="email"
                                            value="{{ Auth::user()->email }}" disabled>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="form-group">
                                        <label for="name">Subject</label>
                                        <input class="form-control" type="text" name="subject" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="name">Department</label>
                                        <select name="department" id="department" class="form-control" required>
                                            <option value="Billing">Billing</option>
                                            <option value="Technical Support">Technical Support</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <div class="form-group">
                                        <label for="name">Priority</label>
                                        <select name="priority" id="priority" class="form-control" required>
                                            <option value="Low">Low</option>
                                            <option value="Medium">Medium</option>
                                            <option value="High">High</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-12 col-md-12">
                                    <div class="form-group">
                                        <label for="name">Message</label>
                                        <textarea class="form-control" name="message" required id="message" cols="30" rows="6"></textarea>
                                    </div>
                                </div>
                            </div>

                            <div class="row mb-4">
                                <div class="col-12 col-md-12">
                                    <div class="form-group">
                                        <label for="name">Attachment</label>
                                        <input type="file" name="attachment" id="attachment" class="form-control">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-2">
                                <div class="col-12 col-md-12">
                                    <div class="form-group" style="display: flex;justify-content: space-around;">
                                        <a href="{{ route('supporttikits.index') }}"
                                            class="btn btn-secondary">Cancel</a>
                                        <button type="submit" class="btn btn-primary">Submit</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>


@endsection
