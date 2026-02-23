@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}- Edit Support Ticket
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{url('/admindashboard')}}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ url('admin/supporttikits') }}">Support Tickets</a></li>
                <li class="breadcrumb-item active">Edit Ticket #000{{ $tikit->id }}</li>
            </ol>
        </nav>
    </div>

    {{-- Ticket Edit Form --}}
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">
                <i class="bi bi-ticket-detailed me-2"></i>Edit Ticket #000{{ $tikit->id }}
            </h6>
            <div class="admin-card-actions">
                @php
                    $statusColors = [
                        'Processing' => 'warning',
                        'Inprogress' => 'info',
                        'Customer-Replay' => 'primary',
                        'Answered' => 'success',
                        'Closed' => 'secondary',
                    ];
                    $badgeColor = $statusColors[$tikit->status] ?? 'secondary';
                @endphp
                <span class="badge bg-{{ $badgeColor }}">{{ $tikit->status }}</span>
            </div>
        </div>
        <div class="admin-card-body">
            <form action="{{ url('admin/supporttikit/update/' . $tikit->id) }}" method="post"
                  enctype="multipart/form-data">
                @csrf
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Name</label>
                        <input class="form-control" type="text" name="name"
                               value="{{ $tikit->name }}" disabled>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Email / Phone</label>
                        <input class="form-control" type="text" name="email"
                               value="{{ $tikit->email }}" disabled>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Subject</label>
                        <input class="form-control" value="{{ $tikit->subject }}" type="text"
                               name="subject" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Department</label>
                        <select name="department" id="department" class="form-select" required>
                            <option @if ($tikit->department == 'Billing') selected @endif
                                value="Billing">Billing</option>
                            <option @if ($tikit->department == 'Technical Support') selected @endif
                                value="Technical Support">Technical Support</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Priority</label>
                        <select name="priority" id="priority" class="form-select" required>
                            <option @if ($tikit->priority == 'Low') selected @endif
                                value="Low">Low</option>
                            <option @if ($tikit->priority == 'Medium') selected @endif
                                value="Medium">Medium</option>
                            <option @if ($tikit->priority == 'High') selected @endif
                                value="High">High</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="message" required id="message" cols="30" rows="5">{{ $tikit->message }}</textarea>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="attachment" id="attachment" class="form-control">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Current Attachment</label>
                        <div>
                            @if($tikit->attachment)
                                <img src="{{ asset($tikit->attachment) }}" alt="Ticket Attachment"
                                     style="height: 100px; border-radius: 8px; border: 1px solid var(--admin-border);">
                            @else
                                <span class="text-muted">No attachment</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option @if ($tikit->status == 'Processing') selected @endif
                                value="Processing">Processing</option>
                            <option @if ($tikit->status == 'Inprogress') selected @endif
                                value="Inprogress">Inprogress</option>
                            <option @if ($tikit->status == 'Customer-Replay') selected @endif
                                value="Customer-Replay">Customer-Replay</option>
                            <option @if ($tikit->status == 'Answered') selected @endif
                                value="Answered">Answered</option>
                            <option @if ($tikit->status == 'Closed') selected @endif
                                value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-3">
                    <a href="{{ url('admin/supporttikits') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Ticket</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Ticket Replies --}}
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">
                <i class="bi bi-chat-left-text me-2"></i>Replies for Ticket #000{{ $tikit->id }}
            </h6>
            <div class="admin-card-actions">
                <span class="badge bg-primary">{{ count($replays) }} {{ count($replays) == 1 ? 'Reply' : 'Replies' }}</span>
            </div>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                    <tr>
                        <th style="width: 60px;">ID</th>
                        <th>Message</th>
                        <th style="width: 120px;">Status</th>
                        <th style="width: 160px;">Updated</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($replays as $replay)
                        <tr>
                            <td>{{ $replay->id }}</td>
                            <td>
                                {{ $replay->replay }}
                                @if($replay->replayatt)
                                    <div class="mt-2">
                                        <img src="{{ asset($replay->replayatt) }}" alt="Reply Attachment"
                                             style="height: 60px; border-radius: 6px; border: 1px solid var(--admin-border);">
                                    </div>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColors[$replay->status] ?? 'secondary' }}">{{ $replay->status }}</span>
                            </td>
                            <td>{{ $replay->updated_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">No replies yet</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Reply Form --}}
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">
                <i class="bi bi-reply me-2"></i>Send Reply
            </h6>
        </div>
        <div class="admin-card-body">
            <form action="{{ url('admin/replay/tikit/' . $tikit->id) }}" method="post"
                  enctype="multipart/form-data">
                @csrf
                <input type="text" name="type" value="Admin" class="form-control" hidden>

                <div class="row g-3 mb-3">
                    <div class="col-12">
                        <label class="form-label">Message</label>
                        <textarea class="form-control" name="replay" required id="replay" cols="30" rows="5"></textarea>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label class="form-label">Attachment</label>
                        <input type="file" name="replayatt" id="replayatt" class="form-control">
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ url('admin/supporttikits') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Send Reply</button>
                </div>
            </form>
        </div>
    </div>
</div>


@endsection
