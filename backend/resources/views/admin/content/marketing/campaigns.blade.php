@extends('admin.master')

@section('content')
<div class="pagetitle">
    <h1>Marketing Campaigns</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Home</a></li>
            <li class="breadcrumb-item active">Marketing Campaigns</li>
        </ol>
    </nav>
</div>

@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    @foreach($errors->all() as $error)
        <div>{{ $error }}</div>
    @endforeach
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<!-- Create Campaign Card -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Generate Registration Link</h5>
    </div>
    <div class="card-body pt-4">
        <form action="{{ url('admin/marketing-campaigns') }}" method="POST">
            @csrf
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="name" class="form-label">Campaign Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           placeholder="e.g. Sahib's Campaign" required>
                </div>
                <div class="col-md-4">
                    <label for="code" class="form-label">Unique Code (used in URL)</label>
                    <div class="input-group">
                        <span class="input-group-text">/registration/</span>
                        <input type="text" class="form-control" id="code" name="code"
                               placeholder="e.g. sahib" required
                               pattern="[a-zA-Z0-9_-]+"
                               title="Only letters, numbers, hyphens, and underscores">
                    </div>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-link-45deg me-1"></i> Generate Link
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Campaigns Table -->
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0"><i class="bi bi-bar-chart me-2"></i>All Campaigns ({{ $campaigns->count() }})</h5>
    </div>
    <div class="card-body pt-3">
        @if($campaigns->count() > 0)
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th>#</th>
                        <th>Campaign Name</th>
                        <th>Registration Link</th>
                        <th class="text-center">Signups</th>
                        <th class="text-center">Subscriptions</th>
                        <th class="text-center">Active</th>
                        <th>Created</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns as $i => $campaign)
                    @php $stats = $campaign->stats; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $campaign->name }}</strong>
                            <br><small class="text-muted">Code: {{ $campaign->code }}</small>
                        </td>
                        <td>
                            <div class="input-group input-group-sm" style="max-width: 400px;">
                                <input type="text" class="form-control form-control-sm bg-light"
                                       value="{{ $frontendUrl }}/registration/{{ $campaign->code }}"
                                       id="link-{{ $campaign->id }}" readonly>
                                <button class="btn btn-outline-primary btn-sm"
                                        onclick="copyLink({{ $campaign->id }})"
                                        title="Copy link">
                                    <i class="bi bi-clipboard" id="icon-{{ $campaign->id }}"></i>
                                </button>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-info fs-6">{{ $stats['signups'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-success fs-6">{{ $stats['subscriptions'] }}</span>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-primary fs-6">{{ $stats['active'] }}</span>
                        </td>
                        <td>{{ $campaign->created_at->format('M d, Y') }}</td>
                        <td class="text-center">
                            <form action="{{ url('admin/marketing-campaigns/' . $campaign->id) }}"
                                  method="POST"
                                  onsubmit="return confirm('Delete this campaign? This will NOT delete users who registered via this link.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-5 text-muted">
            <i class="bi bi-megaphone fs-1"></i>
            <p class="mt-2">No campaigns yet. Create your first registration link above!</p>
        </div>
        @endif
    </div>
</div>

<script>
function copyLink(id) {
    const input = document.getElementById('link-' + id);
    const icon = document.getElementById('icon-' + id);
    input.select();
    document.execCommand('copy');
    icon.className = 'bi bi-check-lg text-success';
    setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
}
</script>
@endsection
