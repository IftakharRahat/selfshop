@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - CRM Users
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="pagetitle mb-3">
        <nav>
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/dashboard') }}">Home</a></li>
                <li class="breadcrumb-item"><a href="{{ route('admin.crm.dashboard') }}">CRM</a></li>
                <li class="breadcrumb-item active">Users</li>
            </ol>
        </nav>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">All User</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($allUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Active User</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($activeUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Total Paid User</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($paidUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Total Unpaid User</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($unpaidUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Expired User</div>
                    <div style="font-size: 20px; font-weight: 700; color: #dc3545;">{{ number_format($expiredUsers) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-2">
            <div class="admin-content-card">
                <div class="admin-card-body text-center">
                    <div class="small text-muted">Total User Account Balance</div>
                    <div style="font-size: 20px; font-weight: 700;">{{ number_format($totalUserAccountBalance, 2) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-content-card">
        <div class="admin-card-header">
            <h6 class="admin-card-title">CRM Users</h6>
        </div>
        <div class="admin-card-body">
            <form method="GET" action="{{ route('admin.crm.users') }}" class="row g-2 mb-0">
                <div class="col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, email, phone" value="{{ $search }}">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All status</option>
                        <option value="Active" {{ $status === 'Active' ? 'selected' : '' }}>Active</option>
                        <option value="Inactive" {{ $status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                        <option value="Block" {{ $status === 'Block' ? 'selected' : '' }}>Block</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="membership" class="form-select form-select-sm">
                        <option value="">All membership</option>
                        <option value="Paid" {{ strtolower($membership) === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="Unpaid" {{ strtolower($membership) === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm w-100" style="background: var(--admin-primary, #2d2a5d); color: #fff;">Filter</button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('admin.crm.users') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
        <div class="admin-card-body p-0" style="border-top: 1px solid var(--admin-border, #f1f5f9);">
            <div class="table-responsive">
                <table class="table admin-table mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Membership</th>
                            <th>Account Balance</th>
                            <th>Created</th>
                            <th>Account Edit Option</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $user)
                            <tr>
                                <td>{{ $user->id }}</td>
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->phone }}</td>
                                <td>
                                    <span class="badge {{ $user->status === 'Active' ? 'bg-success' : ($user->status === 'Block' ? 'bg-danger' : 'bg-secondary') }}">
                                        {{ $user->status ?? 'Inactive' }}
                                    </span>
                                </td>
                                <td>{{ $user->membership_status ?? 'Unpaid' }}</td>
                                <td>{{ number_format((float) $user->account_balance, 2) }}</td>
                                <td>{{ optional($user->created_at)->format('Y-m-d H:i') }}</td>
                                <td>
                                    <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-sm btn-primary">Edit Account</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="admin-card-body d-flex justify-content-center">
            {{ $users->links() }}
        </div>
    </div>
</div>
@endsection

