@extends('user.master')

@section('title')
    {{ env('APP_NAME') }} - Notifications
@endsection

@section('maincontent')
<div class="outer-top-xs outer-bottom-xs">
    <div class="container pt-4 mt-4">
        <div class="row">
            <div class="col-12">
                <div class="card card-body" style="border: none; border-radius: 10px;">
                    <div class="d-flex justify-content-between align-items-center flex-wrap mb-3">
                        <div>
                            <h4 class="mb-1">Notifications</h4>
                            <p class="mb-0 text-muted">Unread: <strong>{{ number_format($unreadCount) }}</strong></p>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('user.notifications') }}" class="btn btn-sm {{ request('filter') !== 'unread' ? 'btn-dark' : 'btn-outline-dark' }}">All</a>
                            <a href="{{ route('user.notifications', ['filter' => 'unread']) }}" class="btn btn-sm {{ request('filter') === 'unread' ? 'btn-dark' : 'btn-outline-dark' }}">Unread</a>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('user.notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">Mark All Read</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @forelse($notifications as $notification)
                        @php
                            $data = is_array($notification->data) ? $notification->data : [];
                            $title = $data['title'] ?? 'Notification';
                            $message = $data['message'] ?? '';
                            $imageUrl = $data['image_url'] ?? null;
                            $link = $data['action_url'] ?? ($data['link'] ?? null);
                            $isRead = $notification->read_at !== null;
                            $linkIsExternal = $link && (str_starts_with($link, 'http://') || str_starts_with($link, 'https://'));
                        @endphp
                        <div class="p-3 mb-3 rounded" style="border: 1px solid {{ $isRead ? '#e5e7eb' : '#f59e0b' }}; background: {{ $isRead ? '#fff' : '#fffaf0' }};">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1" style="font-weight: 700;">
                                        {{ $title }}
                                        @if(!$isRead)
                                            <span class="badge bg-warning text-dark ms-1">New</span>
                                        @endif
                                    </h6>
                                    <small class="text-muted">{{ optional($notification->created_at)->diffForHumans() }}</small>
                                </div>
                                @if(!$isRead)
                                    <form method="POST" action="{{ route('user.notifications.read', $notification->id) }}">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-secondary">Mark Read</button>
                                    </form>
                                @endif
                            </div>

                            @if($imageUrl)
                                <div class="mb-2">
                                    <img src="{{ $imageUrl }}" alt="notification image" style="max-width: 180px; border-radius: 6px;">
                                </div>
                            @endif

                            <p class="mb-0" style="white-space: pre-wrap;">{{ $message }}</p>

                            @if($link)
                                <div class="mt-2">
                                    <a href="{{ $link }}" class="btn btn-sm btn-dark" @if($linkIsExternal) target="_blank" rel="noopener noreferrer" @endif>
                                        Open Link
                                    </a>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="alert alert-info mb-0">
                            No notifications found.
                        </div>
                    @endforelse

                    <div class="d-flex justify-content-center pt-2">
                        {!! $notifications->links('pagination::bootstrap-4') !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
