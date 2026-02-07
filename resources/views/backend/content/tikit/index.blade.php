@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Admin Support Tickets
@endsection
<style>
    a {
        color: black;
        text-decoration: none;
    }

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
            <div class="" style="display: flex;justify-content: space-between;">
                <h4 id="titlename">Ticket Lists</h4>
            </div>
            <div class="row">
                <div class="col-12 col-md-12">
                    <div class="card p-4">
                        <table class="table table-bordered">
                            <thead>
                                <tr class="text-center">
                                    <th scope="col">Department</th>
                                    <th scope="col">Subject</th>
                                    <th scope="col">Status</th>
                                    <th scope="col">Last Update</th>
                                    <th scope="col">Action</th>
                                </tr>
                            </thead>
                            <tbody class="text-center">
                                @forelse ($tikits as $tikit)
                                    <tr>
                                        <th scope="row">
                                            <a href="{{ route('supporttikits.show', $tikit->id) }}">
                                                {{ $tikit->department }}
                                            </a>
                                        </th>
                                        <td>
                                            <a href="{{ route('supporttikits.show', $tikit->id) }}">
                                                <span
                                                    style="float: left;color: green;font-weight: bold;">#000{{ $tikit->id }}</span>
                                                <br>{{ $tikit->subject }}
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('supporttikits.show', $tikit->id) }}">
                                                <button class="btn btn-secondary">{{ $tikit->status }}</button>
                                            </a>
                                        </td>
                                        <td>
                                            <a href="{{ route('supporttikits.show', $tikit->id) }}">
                                                {{ $tikit->updated_at }}
                                            </a>
                                        </td>
                                        <td>
                                            <a class="btn btn-info"
                                                href="{{ url('admin/supporttikit/edit/' . $tikit->id) }}">
                                                Edit
                                            </a>
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
    </div>
</div>

@endsection
