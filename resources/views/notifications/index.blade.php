@extends('layouts.app')

@section('title', 'Low Stock Alerts')

@section('heading')
    Low Stock <span class="text-warning fw-bold">Alerts</span>
@endsection

@section('actions')
    @if (auth()->user()->unreadNotifications->isNotEmpty())
        <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn btn-outline-warning btn-sm px-3">
                <i class="bi bi-check2-all me-1"></i> Mark All as Read
            </button>
        </form>
    @endif
@endsection

@section('content')
    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-bell-fill text-warning me-2"></i> Notification Inbox</h5>
            <small class="text-muted">{{ auth()->user()->unreadNotifications->count() }} unread</small>
        </div>
        <div class="card-body p-0">
            @forelse ($notifications as $notification)
                @php $data = is_array($notification->data) ? $notification->data : []; @endphp
                <div class="d-flex align-items-start gap-3 border-bottom px-4 py-3 {{ $notification->read_at ? '' : 'bg-light' }}">
                    <i class="bi {{ $data['icon'] ?? 'bi-exclamation-triangle-fill' }} text-warning" style="font-size: 1.4rem;"></i>
                    <div class="flex-grow-1">
                        <div class="fw-bold">
                            {{ $data['title'] ?? 'Inventory alert' }}
                            @unless ($notification->read_at)
                                <span class="badge badge-low ms-1">New</span>
                            @endunless
                        </div>
                        <div class="small text-muted">{{ $data['message'] ?? '' }}</div>
                        <div class="small mt-1">
                            <span class="badge badge-category">{{ $data['sku'] ?? '' }}</span>
                            <span class="text-muted ms-2">{{ $notification->created_at->format('M d, Y h:i A') }}</span>
                        </div>
                        @if (! empty($data['suggested_action']))
                            <div class="small text-warning-dark mt-1"><i class="bi bi-lightbulb me-1"></i>{{ $data['suggested_action'] }}</div>
                        @endif
                    </div>
                    <div class="text-nowrap">
                        @if (! empty($data['url']))
                            <a href="{{ $data['url'] }}" class="btn btn-sm btn-outline-primary" title="Open product">
                                <i class="bi bi-eye"></i>
                            </a>
                        @endif
                        @unless ($notification->read_at)
                            <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Mark as read">
                                    <i class="bi bi-check2"></i>
                                </button>
                            </form>
                        @endunless
                    </div>
                </div>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No alerts yet. You will be notified when stock reaches its reorder level.</p>
                </div>
            @endforelse
        </div>
        @if ($notifications->hasPages())
            <div class="card-body border-top">{{ $notifications->links() }}</div>
        @endif
    </div>
@endsection
