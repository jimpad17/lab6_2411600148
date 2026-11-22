@extends('layouts.app')

@section('title', 'Activity Log')

@section('heading')
    Activity <span class="text-warning fw-bold">Log</span>
@endsection

@section('content')
    <p class="text-muted small mb-3"><i class="bi bi-info-circle me-1"></i>
        Track every page visit, search, sign-in and inventory change made in JimTrack.</p>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Total Actions</h6>
                    <h2 class="fw-bold text-primary mb-0">{{ $totalActions }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Actions Today</h6>
                    <h2 class="fw-bold text-success mb-0">{{ $actionsToday }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Users Recorded</h6>
                    <h2 class="fw-bold text-warning mb-0">{{ $activeUsers }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Modules</h6>
                    <h2 class="fw-bold text-danger mb-0">{{ $modulesCount }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('activity-logs.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Search details</label>
                    <input type="text" name="search" class="form-control" placeholder="Action, user, or details" value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Action</label>
                    <select name="action" class="form-select">
                        <option value="">All actions</option>
                        @foreach ($actionOptions as $option)
                            <option value="{{ $option }}" {{ ($filters['action'] ?? '') === $option ? 'selected' : '' }}>{{ ucfirst($option) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Module</label>
                    <select name="module" class="form-select">
                        <option value="">All modules</option>
                        @foreach ($moduleOptions as $option)
                            <option value="{{ $option }}" {{ ($filters['module'] ?? '') === $option ? 'selected' : '' }}>{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All users</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" {{ (string) ($filters['user_id'] ?? '') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small fw-bold text-muted mb-1">Period</label>
                    <select name="period" class="form-select">
                        <option value="">All</option>
                        <option value="today" {{ ($filters['period'] ?? '') === 'today' ? 'selected' : '' }}>Today</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('activity-logs.index') }}" class="btn btn-outline-secondary btn-sm px-3">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 pb-2 d-flex justify-content-between align-items-center border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-warning me-2"></i> Audit Trail</h5>
            <small class="text-muted">Showing {{ $logs->firstItem() ?? 0 }}–{{ $logs->lastItem() ?? 0 }} of {{ $logs->total() }} actions</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Time</th>
                            <th>User</th>
                            <th>Action</th>
                            <th>Module</th>
                            <th>Details</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($logs as $log)
                            <tr>
                                <td class="ps-4 text-nowrap">
                                    <div>{{ $log->created_at->format('M d, Y') }}</div>
                                    <small class="text-muted">{{ $log->created_at->format('h:i A') }}</small>
                                </td>
                                <td><strong>{{ $log->user?->name ?? 'System' }}</strong></td>
                                <td>
                                    <span class="badge {{ $log->action === 'logged in' ? 'badge-in' : ($log->action === 'logged out' ? 'bg-secondary' : 'badge-category') }}">
                                        {{ $log->action_label }}
                                    </span>
                                </td>
                                <td>{{ $log->module }}</td>
                                <td>{{ $log->description }}</td>
                                <td><code>{{ $log->ip_address ?? '—' }}</code></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No activity recorded for this filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $logs->links() }}</div>
    </div>
@endsection
