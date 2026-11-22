@extends('layouts.app')

@section('title', 'My Profile')

@section('heading')
    My <span class="text-warning fw-bold">Profile</span>
@endsection

@section('content')
    <div class="row">
        <div class="col-lg-5 mb-3">
            <div class="card stat-card shadow-sm h-100 py-4">
                <div class="card-body text-center">
                    <i class="bi bi-person-circle text-warning" style="font-size:3rem;"></i>
                    <h4 class="fw-bold mt-3 mb-1">{{ $user->name }}</h4>
                    <span class="badge role-badge">{{ strtoupper($user->role) }}</span>
                    <hr>
                    <div class="text-start small">
                        <div class="mb-2"><span class="text-muted d-block">Email</span><strong>{{ $user->email }}</strong></div>
                        <div class="mb-2"><span class="text-muted d-block">Account Status</span>
                            <strong class="{{ $user->is_active ? 'text-success' : 'text-danger' }}">{{ $user->is_active ? 'Active' : 'Inactive' }}</strong></div>
                        <div class="mb-2"><span class="text-muted d-block">Member Since</span>
                            <strong>{{ $user->created_at?->format('M d, Y') }}</strong></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-7 mb-3">
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
