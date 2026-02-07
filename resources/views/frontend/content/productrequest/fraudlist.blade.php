<div class="card card-body mb-3">
    @forelse ($frauds as $fraud)
        <div class="card card-body info mb-3">
            <div class="d-flex justify-content-between">
                <p>{{ $fraud->phone }}</p>
                <button class="btn btn-info btn-sm" style="border-radius: 4px;">{{ $fraud->status }}</button>
            </div>
            <p class="m-0">{{ $fraud->message }}</p>
        </div>
    @empty

    @endforelse
</div>
