@extends('backend.master')

@section('maincontent')

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
            <h4 id="titlename">Edit Ticket No - #000{{ $tikit->id }}</h4>
            <div class="row mb-4">
                <div class="col-12 col-md-12">
                    <div class="card mb-4">
                        <div class="card-header" id="heading" style="border: 1px solid skyblue;">
                            <h5 class="m-0" style="display: flex;justify-content: space-between;">
                                <a type="button" id="qtn" data-bs-toggle="collapse"
                                    style="text-decoration: none;color:black" data-bs-target="#collapseTikit"
                                    aria-expanded="true" aria-controls="collapseTikit">
                                    #000{{ $tikit->id }} >>> &nbsp;{{ $tikit->subject }}
                                </a>
                                <span><i class="fa fa-sort-down"></i></span>
                            </h5>
                        </div>

                        <div id="collapseTikit" class="collapse show" aria-labelledby="heading"
                            data-parent="#accordion">
                            <div class="card-body">
                                <form action="{{ url('admin/supporttikit/update/' . $tikit->id) }}" method="post"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <div class="row mb-2">
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label for="name">Name</label>
                                                <input class="form-control" type="text" name="name"
                                                    value="{{ $tikit->name }}" disabled>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label for="name">Email / Phone</label>
                                                <input class="form-control" type="text" name="email"
                                                    value="{{ $tikit->email }}" disabled>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group">
                                                <label for="name">Subject</label>
                                                <input class="form-control" value="{{ $tikit->subject }}" type="text"
                                                    name="subject" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label for="name">Department</label>
                                                <select name="department" id="department" class="form-control" required>
                                                    <option @if ($tikit->department == 'Billing') selected  @else @endif
                                                        value="Billing">Billing</option>
                                                    <option @if ($tikit->department == 'Technical Support') selected  @else @endif
                                                        value="Technical Support">Technical Support</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <div class="form-group">
                                                <label for="name">Priority</label>
                                                <select name="priority" id="priority" class="form-control" required>
                                                    <option @if ($tikit->priority == 'Low') selected  @else @endif
                                                        value="Low">Low</option>
                                                    <option @if ($tikit->priority == 'Medium') selected  @else @endif
                                                        value="Medium">Medium</option>
                                                    <option @if ($tikit->priority == 'High') selected  @else @endif
                                                        value="High">High</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-12 col-md-12">
                                            <div class="form-group">
                                                <label for="name">Message</label>
                                                <textarea class="form-control" name="message" required id="message" cols="30" rows="6">{{ $tikit->message }}</textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-12 col-md-12">
                                            <div class="form-group">
                                                <label for="name">Attachment</label>
                                                <input type="file" name="attachment" id="attachment"
                                                    class="form-control">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-2">
                                        <div class="col-12 col-md-12">
                                            <div class="form-group">
                                                <label for="name">Attachment</label>
                                                <img src="{{ asset($tikit->attachment) }}" alt=""
                                                    height="120px">
                                            </div>
                                        </div>

                                        <div class="col-12 col-md-12">
                                            <div class="form-group">
                                                <label for="name">Status</label>
                                                <select name="department" id="department" class="form-control" required>
                                                    <option @if ($tikit->status == 'Processing') selected  @else @endif
                                                        value="Processing">Processing</option>
                                                    <option @if ($tikit->status == 'Inprogress') selected  @else @endif
                                                        value="Inprogress">Inprogress</option>
                                                    <option @if ($tikit->status == 'Customer-Replay') selected  @else @endif
                                                        value="Customer-Replay">Customer-Replay</option>
                                                    <option @if ($tikit->status == 'Answered') selected  @else @endif
                                                        value="Answered">Answered</option>
                                                    <option @if ($tikit->status == 'Closed') selected  @else @endif
                                                        value="Closed">Closed</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-2">
                                        <div class="col-12 col-md-12">
                                            <div class="form-group"
                                                style="display: flex;justify-content: space-around;">
                                                <a href="{{ url('admin/supporttikits') }}"
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

            <h4 id="titlename">Replay Of Ticket - #000{{ $tikit->id }}</h4>
            <div class="col-12 col-md-12 mb-4">
                <div class="card p-4">
                    <table class="table table-bordered">
                        <thead>
                            <tr class="text-center">
                                <th scope="col">ID</th>
                                <th scope="col">Message</th>
                                <th scope="col">Status</th>
                                <th scope="col">Update</th>
                            </tr>
                        </thead>
                        <tbody class="text-center">
                            @forelse ($replays as $replay)
                                <tr>
                                    <th scope="row">
                                        {{ $replay->id }}
                                    </th>
                                    <th scope="row">
                                        {{ $replay->replay }}
                                        <br>
                                        <img src="{{ asset($replay->replayatt) }}" alt="" height="80px">
                                    </th>
                                    <td>
                                        {{ $replay->status }}
                                    </td>
                                    <td>
                                        {{ $replay->updated_at }}
                                    </td>
                                </tr>
                            @empty
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-md-12">
                    <div class="card p-4">
                        <form action="{{ url('admin/replay/tikit/' . $tikit->id) }}" method="post"
                            enctype="multipart/form-data">
                            @csrf
                            <div class="row mb-2">

                                <div class="row mb-2">
                                    <div class="col-12 col-md-12">
                                        <div class="form-group">
                                            <label for="name">Message</label>
                                            <textarea class="form-control" name="replay" required id="replay" cols="30" rows="6"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <input type="text" name="type" value="Admin" class="form-control" hidden>

                                <div class="row mb-4">
                                    <div class="col-12 col-md-12">
                                        <div class="form-group">
                                            <label for="name">Attachment</label>
                                            <input type="file" name="replayatt" id="attachment"
                                                class="form-control">
                                        </div>
                                    </div>
                                </div>


                                <div class="row mt-2">
                                    <div class="col-12 col-md-12">
                                        <div class="form-group" style="display: flex;justify-content: space-around;">
                                            <a href="{{ url('admin/supporttikits') }}"
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
