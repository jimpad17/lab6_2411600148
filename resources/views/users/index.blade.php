@extends('layouts.app')

@section('title', 'Users')

@section('heading')
    System <span class="text-warning fw-bold">Users</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Total Accounts</h6>
                    <h2 class="fw-bold text-primary mb-0">{{ \App\Models\User::count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Admins</h6>
                    <h2 class="fw-bold text-warning mb-0">{{ \App\Models\User::where('role', 'admin')->active()->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Staff</h6>
                    <h2 class="fw-bold text-success mb-0">{{ \App\Models\User::where('role', 'staff')->active()->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Inactive</h6>
                    <h2 class="fw-bold text-danger mb-0">{{ \App\Models\User::where('is_active', false)->count() }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('users.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Name or email..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Role</label>
                    <select name="role" class="form-select">
                        <option value="">All Roles</option>
                        <option value="admin" {{ ($filters['role'] ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="staff" {{ ($filters['role'] ?? '') === 'staff' ? 'selected' : '' }}>Staff</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm px-3">Clear</a>
                </div>
                <div class="col-md-3 text-md-end">
                    <a href="{{ route('users.create') }}" class="btn btn-success btn-sm px-3">
                        <i class="bi bi-person-plus me-1"></i> Add User
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-people text-warning me-2"></i> User Accounts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="{{ $user->is_active ? '' : 'table-inactive' }}">
                                <td class="ps-4">
                                    <strong>{{ $user->name }}</strong>
                                    @if ($user->id === auth()->id())
                                        <span class="badge bg-info text-dark ms-1">You</span>
                                    @endif
                                    @if ($user->isProtectedAdmin())
                                        <span class="badge bg-dark ms-1" title="Default admin account cannot be deactivated">Protected</span>
                                    @endif
                                </td>
                                <td><small>{{ $user->email }}</small></td>
                                <td><span class="badge {{ $user->role === 'admin' ? 'bg-warning text-dark' : 'bg-secondary' }}">{{ ucfirst($user->role) }}</span></td>
                                <td>
                                    <span class="badge {{ $user->is_active ? 'badge-in' : 'badge-inactive' }}">
                                        {{ $user->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td><small>{{ $user->created_at->format('M d, Y') }}</small></td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    @if ($user->is_active)
                                        @if ($user->id !== auth()->id() && ! $user->isProtectedAdmin())
                                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                                                  onsubmit="return confirm('Deactivate \'{{ addslashes($user->name) }}\'? No records will be deleted.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <form method="POST" action="{{ route('users.restore', $user) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No user accounts found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top"><small class="text-muted">Deactivated accounts are kept for the audit trail and can be restored.</small>{{ $users->links() }}</div>
    </div>
@endsection
