@extends('layouts.app')

@section('title', 'My Profile')

@section('heading')
    My <span class="text-warning fw-bold">Profile</span>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-4 mb-3">
            <div class="card stat-card shadow-sm h-100 py-4">
                <div class="card-body text-center">
                    @if ($user->avatar_url)
                        <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="rounded-circle mb-3 border border-3 border-warning" style="width:96px;height:96px;object-fit:cover;">
                    @else
                        <div class="rounded-circle bg-warning bg-opacity-25 text-warning-dark d-inline-flex align-items-center justify-content-center fw-bold mb-3" style="width:96px;height:96px;font-size:2rem;">{{ $user->initials }}</div>
                    @endif
                    <h4 class="fw-bold mt-1 mb-1">{{ $user->name }}</h4>
                    <span class="badge role-badge">{{ strtoupper($user->role) }}</span>
                    <hr>
                    <div class="text-start small">
                        <div class="mb-2"><span class="text-muted d-block">Email</span><strong>{{ $user->email }}</strong></div>
                        <div class="mb-2"><span class="text-muted d-block">Account Status</span>
                            <strong class="{{ $user->is_active ? 'text-success' : 'text-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</strong></div>
                        <div class="mb-2"><span class="text-muted d-block">Member Since</span>
                            <strong>{{ $user->created_at?->format('M d, Y') }}</strong></div>
                        <div class="mb-2"><span class="text-muted d-block">Last Updated</span>
                            <strong>{{ $user->updated_at?->format('M d, Y h:i A') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-3">
            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                        <h6 class="card-title text-uppercase fw-bold mb-2">Active Products</h6>
                        <h2 class="fw-bold text-primary mb-0">{{ $productCount }}</h2>
                        <small class="text-muted">Available in catalog</small>
                    </div></div>
                </div>
                <div class="col-md-6">
                    <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                        <h6 class="card-title text-uppercase fw-bold mb-2">Low Stock</h6>
                        <h2 class="fw-bold text-warning mb-0">{{ $lowStockCount }}</h2>
                        <small class="text-muted">Need reordering</small>
                    </div></div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Edit Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="form-control form-control-sm" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="form-control form-control-sm" required @if ($user->isProtectedAdmin()) readonly @endif>
                                @if ($user->isProtectedAdmin())
                                    <small class="text-muted">The protected admin email cannot be changed.</small>
                                @endif
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Profile Picture <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="file" name="avatar" accept="image/*" class="form-control form-control-sm">
                                <small class="text-muted">JPG, PNG, GIF or WEBP, up to 2 MB.</small>
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i> Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-key text-warning me-2"></i> Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.password') }}">
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Current Password</label>
                                <input type="password" name="current_password" class="form-control form-control-sm" required autocomplete="current-password">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">New Password</label>
                                <input type="password" name="password" class="form-control form-control-sm" required minlength="8" autocomplete="new-password">
                                <small class="text-muted">At least 8 characters.</small>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Confirm New Password</label>
                                <input type="password" name="password_confirmation" class="form-control form-control-sm" required minlength="8" autocomplete="new-password">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-warning btn-sm fw-bold"><i class="bi bi-shield-lock me-1"></i> Update Password</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 text-warning me-2"></i> My Recent Orders</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light"><tr><th class="ps-3">Order #</th><th>Product</th><th>Qty</th><th>Date</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($recentOrders as $order)
                                    <tr>
                                        <td class="ps-3"><a href="{{ route('orders.show', $order) }}" class="text-decoration-none"><code>{{ $order->order_number }}</code></a></td>
                                        <td>{{ $order->product?->name ?? 'Deleted product' }}</td>
                                        <td>{{ $order->quantity }}</td>
                                        <td>{{ $order->order_date->format('M d, Y') }}</td>
                                        <td><span class="badge {{ $order->badge_class }}">{{ $order->status_label }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">You have not submitted any orders yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
