@extends('layouts.app')

@section('title', 'Orders')

@section('heading')
    Purchase &amp; Sales <span class="text-warning fw-bold">Orders</span>
@endsection

@section('actions')
    <a href="{{ route('orders.create') }}" class="btn btn-success btn-sm px-3">
        <i class="bi bi-plus-circle me-1"></i> New Order
    </a>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Pending Review</h6>
                <h2 class="fw-bold text-warning mb-0">{{ $pendingCount }}</h2>
                <small class="text-muted">Waiting for admin approval</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Approved</h6>
                <h2 class="fw-bold text-success mb-0">{{ $approvedCount }}</h2>
                <small class="text-muted">Posted to inventory</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Sold</h6>
                <h2 class="fw-bold text-primary mb-0">{{ number_format($salesTotal) }}</h2>
                <small class="text-muted">Approved sales orders</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Purchased</h6>
                <h2 class="fw-bold text-success mb-0">{{ number_format($purchaseTotal) }}</h2>
                <small class="text-muted">Approved purchase orders</small>
            </div></div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All types</option>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $value => $label)
                            <option value="{{ $value }}" @selected(($filters['status'] ?? '') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 text-warning me-2"></i> Order Queue</h5>
            <small class="text-muted">{{ $orders->total() }} orders</small>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Order #</th><th>Type</th><th>Product</th><th>Quantity</th>
                            <th>Order Date</th><th>Status</th><th>Submitted By</th><th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($orders as $order)
                            <tr>
                                <td class="ps-4"><code>{{ $order->order_number }}</code></td>
                                <td>
                                    <span class="badge {{ $order->type === 'purchase' ? 'badge-in' : 'badge-category' }}">
                                        {{ $order->type_label }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('products.show', $order->product_id) }}" class="fw-semibold text-decoration-none">
                                        {{ $order->product?->name ?? 'Deleted product' }}
                                    </a>
                                    @if ($order->product)<div><code class="sku-tag">{{ $order->product->sku }}</code></div>@endif
                                </td>
                                <td class="fw-bold">
                                    {{ $order->quantity }}
                                    @if ($order->was_edited)
                                        <div class="text-muted" style="text-decoration: line-through; font-size:.75rem;">{{ $order->original_quantity }}</div>
                                    @endif
                                </td>
                                <td>{{ $order->order_date->format('M d, Y') }}</td>
                                <td><span class="badge {{ $order->badge_class }}">{{ $order->status_label }}</span></td>
                                <td>{{ $order->creator?->name ?? 'System' }}</td>
                                <td class="text-end pe-4 text-nowrap">
                                    <a href="{{ route('orders.show', $order) }}" class="btn btn-sm btn-outline-primary" title="View">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @if (Auth::user()->isAdmin() && $order->status === 'pending')
                                        <a href="{{ route('orders.edit', $order) }}" class="btn btn-sm btn-outline-warning" title="Edit before approval">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('orders.approve', $order) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Approve and post stock"
                                                    onclick="return confirm('Approve this order and update stock?');">
                                                <i class="bi bi-check2"></i>
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('orders.reject', $order) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject"
                                                    onclick="return confirm('Reject this order? No stock will be moved.');">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No orders match your filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $orders->links() }}</div>
    </div>
@endsection
