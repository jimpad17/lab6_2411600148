@extends('layouts.app')

@section('title', 'Daily Report')

@section('heading')
    Daily <span class="text-warning fw-bold">Report</span>
@endsection

@section('actions')
    <form method="GET" action="{{ route('daily-report') }}" class="d-flex gap-2">
        <input type="date" name="date" value="{{ $date }}" class="form-control form-control-sm">
        <button class="btn btn-primary btn-sm"><i class="bi bi-calendar-check me-1"></i> View</button>
    </form>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-3 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Sales Units</h6>
            <h2 class="fw-bold text-success mb-0">{{ number_format($salesUnits) }}</h2>
        </div></div></div>
        <div class="col-md-3 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Sales Value</h6>
            <h2 class="fw-bold text-success mb-0">₱{{ number_format($salesValue, 2) }}</h2>
        </div></div></div>
        <div class="col-md-3 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Purchase Units</h6>
            <h2 class="fw-bold text-warning mb-0">{{ number_format($purchaseUnits) }}</h2>
        </div></div></div>
        <div class="col-md-3 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Purchase Value</h6>
            <h2 class="fw-bold text-warning mb-0">₱{{ number_format($purchaseValue, 2) }}</h2>
        </div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-check text-success me-2"></i> Sales Orders <small class="text-muted fw-normal">{{ \Illuminate\Support\Carbon::parse($date)->format('M d, Y') }}</small></h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Order #</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Encoded By</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($sales as $order)
                            <tr>
                                <td class="ps-4"><a href="{{ route('orders.show', $order) }}" class="text-decoration-none"><code>{{ $order->order_number }}</code></a></td>
                                <td>{{ $order->product?->name ?? 'Deleted product' }}</td>
                                <td class="fw-bold">{{ $order->quantity }}</td>
                                <td>₱{{ number_format($order->unit_price, 2) }}</td>
                                <td class="fw-bold">₱{{ number_format($order->total, 2) }}</td>
                                <td>{{ $order->creator?->name ?? '—' }}</td>
                                <td>{{ $order->order_date->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-3 text-muted">No approved sales orders for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-plus text-warning me-2"></i> Purchase Orders</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Order #</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Encoded By</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($purchases as $order)
                            <tr>
                                <td class="ps-4"><a href="{{ route('orders.show', $order) }}" class="text-decoration-none"><code>{{ $order->order_number }}</code></a></td>
                                <td>{{ $order->product?->name ?? 'Deleted product' }}</td>
                                <td class="fw-bold">{{ $order->quantity }}</td>
                                <td>₱{{ number_format($order->unit_price, 2) }}</td>
                                <td class="fw-bold">₱{{ number_format($order->total, 2) }}</td>
                                <td>{{ $order->creator?->name ?? '—' }}</td>
                                <td>{{ $order->order_date->format('M d, Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-3 text-muted">No approved purchase orders for this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-primary me-2"></i> Stock Movements</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Reference</th><th>Product</th><th>Type</th><th>Qty</th><th>User</th><th>Time</th></tr></thead>
                    <tbody>
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="ps-4"><code>{{ $movement->reference_number }}</code></td>
                                <td>{{ $movement->product?->name ?? 'Deleted product' }}</td>
                                <td><span class="badge text-bg-{{ $movement->type === 'in' ? 'success' : ($movement->type === 'out' ? 'danger' : 'secondary') }}">{{ strtoupper($movement->type) }}</span></td>
                                <td class="fw-bold">{{ $movement->quantity }}</td>
                                <td>{{ $movement->user?->name ?? '—' }}</td>
                                <td>{{ $movement->created_at->format('h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-3 text-muted">No stock movements recorded on this date.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
