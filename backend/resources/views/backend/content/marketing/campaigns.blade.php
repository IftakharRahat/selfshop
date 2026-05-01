@extends('backend.master')

@section('title')
    {{ env('APP_NAME') }} - Marketing Campaigns
@endsection

@section('maincontent')
<div class="container-fluid pt-4 px-4">
    <div class="row mb-4">
        <div class="col-12">
            <h3 class="mb-0"><i class="bi bi-megaphone me-2"></i>Marketing Campaigns</h3>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url('admin/dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Marketing Campaigns</li>
                </ol>
            </nav>
        </div>
    </div>

    <!-- Create Campaign Card -->
    <div class="card card-box mb-4">
        <div class="card-header bg-white">
            <h5 class="card-title mb-0"><i class="bi bi-plus-circle me-2"></i>Generate Registration Link</h5>
        </div>
        <div class="card-body pt-4">
            <form action="{{ url('admin/marketing-campaigns') }}" method="POST">
                @csrf
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label for="name" class="form-label">Campaign Name <small class="text-muted">(optional)</small></label>
                        <input type="text" class="form-control" id="name" name="name"
                               placeholder="e.g. Sahib's Campaign">
                    </div>
                    <div class="col-md-4">
                        <label for="code" class="form-label">Registration Code <small class="text-muted">(optional)</small></label>
                        <div class="input-group">
                            <span class="input-group-text">/registration/</span>
                            <input type="text" class="form-control" id="code" name="code"
                                   placeholder="e.g. sahib">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-link-45deg me-1"></i> Generate Link
                        </button>
                    </div>
                </div>
                <small class="text-muted d-block mt-2">Fill at least one field. Missing values will be auto-generated.</small>
            </form>
        </div>
    </div>

    <!-- Campaigns Table -->
    <div class="card card-box">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0"><i class="bi bi-bar-chart me-2"></i>All Campaigns ({{ $campaigns->count() }})</h5>
        </div>
        <div class="card-body pt-3">
            @if($campaigns->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
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
                                    <input type="text" class="form-control form-control-sm"
                                           value="{{ $frontendUrl }}/registration/{{ $campaign->code }}"
                                           id="link-{{ $campaign->id }}" readonly>
                                    <button class="btn btn-outline-secondary btn-sm"
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
                <i class="bi bi-megaphone" style="font-size: 2rem;"></i>
                <p class="mt-2">No campaigns yet. Create your first registration link above!</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
function copyLink(id) {
    const input = document.getElementById('link-' + id);
    const icon = document.getElementById('icon-' + id);
    input.select();
    document.execCommand('copy');
    icon.className = 'bi bi-check-lg text-success';
    if (typeof toastr !== 'undefined') {
        toastr.success('Link copied to clipboard!');
    }
    setTimeout(() => { icon.className = 'bi bi-clipboard'; }, 2000);
}
</script>
@endsection
