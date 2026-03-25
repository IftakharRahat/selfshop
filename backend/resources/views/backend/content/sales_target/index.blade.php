@extends('backend.master')

@section('maincontent')
@section('title')
    {{ env('APP_NAME') }}- Sales Targets
@endsection

<div class="container-fluid pt-4 px-4">
    <div class="row">
        <div class="col-12">
            <div class="admin-content-card">
                <div class="admin-card-header">
                    <h6 class="admin-card-title">Sales Target Management</h6>
                    <div class="admin-card-actions">
                        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createSalesTargetModal">
                            <i class="bi bi-plus-lg"></i> Add Target
                        </button>
                    </div>
                </div>
                <div class="admin-card-body">
                    <div class="data-tables">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Target</th>
                                    <th>Scope</th>
                                    <th>Reward</th>
                                    <th>Period</th>
                                    <th>Priority</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($targets as $target)
                                    <tr>
                                        <td>{{ $target->id }}</td>
                                        <td>
                                            <strong>{{ $target->title }}</strong>
                                            @if($target->description)
                                                <div class="small text-muted">{{ $target->description }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if($target->target_type === 'amount')
                                                TK {{ number_format($target->target_value, 2) }}
                                            @else
                                                {{ number_format($target->target_value, 2) }} Qty
                                            @endif
                                        </td>
                                        <td>{{ $target->order_scope === 'delivered' ? 'Delivered' : 'Non Canceled' }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ ucfirst($target->reward_type) }}</span>
                                            @if($target->reward_value !== null)
                                                <div class="small">TK {{ number_format($target->reward_value, 2) }}</div>
                                            @endif
                                            @if($target->reward_note)
                                                <div class="small text-muted">{{ $target->reward_note }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                {{ $target->start_date ? $target->start_date->format('d M Y') : 'No start' }}
                                                -
                                                {{ $target->end_date ? $target->end_date->format('d M Y') : 'No end' }}
                                            </div>
                                        </td>
                                        <td>{{ $target->priority }}</td>
                                        <td>
                                            @if($target->status === 'Active')
                                                <span class="badge bg-success">Active</span>
                                            @else
                                                <span class="badge bg-secondary">Inactive</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex gap-1">
                                                <button
                                                    type="button"
                                                    class="btn btn-primary btn-sm editTargetBtn"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#editSalesTargetModal"
                                                    data-id="{{ $target->id }}"
                                                    data-title="{{ $target->title }}"
                                                    data-description="{{ $target->description }}"
                                                    data-target_type="{{ $target->target_type }}"
                                                    data-target_value="{{ $target->target_value }}"
                                                    data-order_scope="{{ $target->order_scope }}"
                                                    data-reward_type="{{ $target->reward_type }}"
                                                    data-reward_value="{{ $target->reward_value }}"
                                                    data-reward_note="{{ $target->reward_note }}"
                                                    data-start_date="{{ $target->start_date ? $target->start_date->format('Y-m-d') : '' }}"
                                                    data-end_date="{{ $target->end_date ? $target->end_date->format('Y-m-d') : '' }}"
                                                    data-priority="{{ $target->priority }}"
                                                    data-status="{{ $target->status }}"
                                                >
                                                    <i class="bi bi-pencil-square"></i>
                                                </button>

                                                <form action="{{ route('admin.sales-targets.toggle-status', $target->id) }}" method="POST">
                                                    @csrf
                                                    @method('PUT')
                                                    <button type="submit" class="btn btn-warning btn-sm">
                                                        <i class="bi bi-arrow-repeat"></i>
                                                    </button>
                                                </form>

                                                <form action="{{ route('admin.sales-targets.destroy', $target->id) }}" method="POST" onsubmit="return confirm('Delete this sales target?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted">No sales targets found.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ $targets->links('vendor.pagination.admin') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="createSalesTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content admin-modal">
            <div class="modal-header admin-modal-header">
                <h5 class="modal-title">Create Sales Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body admin-modal-body">
                <form method="POST" action="{{ route('admin.sales-targets.store') }}">
                    @csrf
                    @include('backend.content.sales_target.partials.form-fields', ['mode' => 'create'])
                    <div class="admin-modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary btn-sm">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">Save</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editSalesTargetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content admin-modal">
            <div class="modal-header admin-modal-header">
                <h5 class="modal-title">Update Sales Target</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body admin-modal-body">
                <form method="POST" id="editSalesTargetForm">
                    @csrf
                    @method('PUT')
                    @include('backend.content.sales_target.partials.form-fields', ['mode' => 'edit'])
                    <div class="admin-modal-footer">
                        <button type="button" data-bs-dismiss="modal" class="btn btn-outline-secondary btn-sm">Close</button>
                        <button type="submit" class="btn btn-primary btn-sm">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var editButtons = document.querySelectorAll('.editTargetBtn');
    var editForm = document.getElementById('editSalesTargetForm');

    editButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            var id = this.dataset.id;
            editForm.action = '/admin/sales-targets/' + id;

            document.getElementById('edit_title').value = this.dataset.title || '';
            document.getElementById('edit_description').value = this.dataset.description || '';
            document.getElementById('edit_target_type').value = this.dataset.target_type || 'amount';
            document.getElementById('edit_target_value').value = this.dataset.target_value || '';
            document.getElementById('edit_order_scope').value = this.dataset.order_scope || 'non_canceled';
            document.getElementById('edit_reward_type').value = this.dataset.reward_type || 'reward';
            document.getElementById('edit_reward_value').value = this.dataset.reward_value || '';
            document.getElementById('edit_reward_note').value = this.dataset.reward_note || '';
            document.getElementById('edit_start_date').value = this.dataset.start_date || '';
            document.getElementById('edit_end_date').value = this.dataset.end_date || '';
            document.getElementById('edit_priority').value = this.dataset.priority || 0;
            document.getElementById('edit_status').value = this.dataset.status || 'Active';
        });
    });
});
</script>

@endsection
