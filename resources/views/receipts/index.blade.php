@extends('layouts.app')

@section('title', 'Receipts')

@section('heading')
    <span class="text-warning fw-bold">Receipts</span>
@endsection

@section('content')
    <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-2"></i>Receipts are generated automatically when an admin approves a purchase or sales order.
    </div>

    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('receipts.index') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">All receipts</option>
                        <option value="sales" @selected(($filters['type'] ?? '') === 'sales')>Sales</option>
                        <option value="purchase" @selected(($filters['type'] ?? '') === 'purchase')>Purchase</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
                <div class="col-md-5 text-end">
                    <span class="text-muted small">Total receipts: <strong>{{ $totalReceipts }}</strong></span>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Receipt #</th><th>Order #</th><th>Type</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th><th>Approved</th><th class="text-end pe-4">Action</th></tr></thead>
                    <tbody>
                        @forelse ($receipts as $receipt)
                            <tr>
                                <td class="ps-4"><code>{{ $receipt->receipt_number }}</code></td>
                                <td><code>{{ $receipt->order_number }}</code></td>
                                <td><span class="badge text-bg-{{ $receipt->type === 'sales' ? 'success' : 'warning' }}">{{ strtoupper($receipt->type) }}</span></td>
                                <td>{{ $receipt->product?->name ?? 'Deleted product' }}</td>
                                <td class="fw-bold">{{ $receipt->quantity }}</td>
                                <td>₱{{ number_format($receipt->unit_price, 2) }}</td>
                                <td class="fw-bold">₱{{ number_format($receipt->total, 2) }}</td>
                                <td>{{ $receipt->approved_at?->format('M d, Y') ?? '—' }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('receipts.show', $receipt) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="bi bi-printer me-1"></i> Print
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="text-center py-4 text-muted">No receipts yet. Approve an order to generate one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $receipts->links() }}</div>
    </div>
@endsection
