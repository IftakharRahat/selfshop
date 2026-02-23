@extends('backend.master')

@section('maincontent')

@section('title')
    {{ env('APP_NAME') }}-Admin Support Tickets
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admindashboard') }}">Home</a></li>
                <li class="breadcrumb-item active">Support Tickets</li>
            </ol>
        </nav>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">Ticket Lists</h6>
        </div>
        <div class="admin-card-body p-0">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Subject</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tikits as $tikit)
                            <tr>
                                <td>
                                    <a href="{{ route('supporttikits.show', $tikit->id) }}" style="color: inherit; text-decoration: none; font-weight: 500;">
                                        {{ $tikit->department }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('supporttikits.show', $tikit->id) }}" style="color: inherit; text-decoration: none;">
                                        <span style="color: var(--admin-primary, #2d2a5d); font-weight: 600;">#000{{ $tikit->id }}</span>
                                        <br>{{ $tikit->subject }}
                                    </a>
                                </td>
                                <td>
                                    <a href="{{ route('supporttikits.show', $tikit->id) }}">
                                        <span class="badge" style="background: #e8e6f0; color: var(--admin-primary, #2d2a5d); padding: 5px 12px; border-radius: 20px; font-weight: 500;">{{ $tikit->status }}</span>
                                    </a>
                                </td>
                                <td style="color: #6c757d; font-size: 13px;">
                                    {{ $tikit->updated_at }}
                                </td>
                                <td>
                                    <a class="btn btn-sm" style="background: #e8e6f0; color: var(--admin-primary, #2d2a5d); border-radius: 6px; font-weight: 500;" href="{{ url('admin/supporttikit/edit/' . $tikit->id) }}">
                                        <i class="bi bi-pencil-square me-1"></i>Edit
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4" style="color: #6c757d;">No tickets found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@endsection
