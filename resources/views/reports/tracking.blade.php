@extends('layouts.app')

@section('title', 'Inventory Tracking Reports')

@section('heading')
    Inventory <span class="text-warning fw-bold">Tracking Reports</span>
@endsection

@section('actions')
    <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary btn-sm px-3">
        <i class="bi bi-arrow-left me-1"></i> Valuation Reports
    </a>
    <a href="{{ route('reports.tracking.export', request()->only('from', 'to')) }}" class="btn btn-primary btn-sm px-3">
        <i class="bi bi-download me-1"></i> Export Movements (CSV)
    </a>
@endsection

@section('content')
    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('reports.tracking') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">From Date</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">To Date</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-6">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i> Generate Report</button>
                    <a href="{{ route('reports.tracking') }}" class="btn btn-outline-secondary btn-sm px-3">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Received</h6>
                <h2 class="fw-bold text-success mb-0">{{ number_format($totalIn) }}</h2>
                <small class="text-muted">Within selected period</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Issued</h6>
                <h2 class="fw-bold text-warning mb-0">{{ number_format($totalOut) }}</h2>
                <small class="text-muted">Within selected period</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Reorder Needed</h6>
                <h2 class="fw-bold text-danger mb-0">{{ $lowStockProducts->count() }}</h2>
                <small class="text-muted">At or below reorder level</small>
            </div></div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Inventory Value</h6>
                <h2 class="fw-bold text-success mb-0" style="font-size: 1.6rem;">₱{{ number_format($totalValue, 2) }}</h2>
                <small class="text-muted">{{ number_format($totalUnits) }} units on hand</small>
            </div></div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-success me-2"></i> Stock Movement Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light sticky-top">
                                <tr><th class="ps-4">Date</th><th>Product</th><th>Type</th><th>Qty</th><th>Balance</th><th>Reference</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($movements->take(50) as $movement)
                                    <tr>
                                        <td class="ps-4 text-nowrap">{{ $movement->created_at->format('M d, Y h:i') }}</td>
                                        <td>{{ $movement->product?->name ?? 'Deleted product' }}</td>
                                        <td><span class="badge {{ $movement->badge_class }}">{{ $movement->type_label }}</span></td>
                                        <td class="fw-bold {{ $movement->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $movement->quantity_display }}</td>
                                        <td>{{ $movement->balance_after ?? '—' }}</td>
                                        <td><code>{{ $movement->reference_number ?? '—' }}</code></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No movements in this date range.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($movements->count() > 50)
                    <div class="card-body border-top"><small class="text-muted">Showing the 50 newest of {{ $movements->count() }} movements. Export CSV for the complete log.</small></div>
                @endif
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Low Stock Report</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive" style="max-height: 420px;">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light sticky-top">
                                <tr><th class="ps-3">Product</th><th>SKU</th><th>On Hand</th><th>Reorder At</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockProducts as $product)
                                    <tr>
                                        <td class="ps-3">{{ $product->name }}</td>
                                        <td><code class="sku-tag">{{ $product->sku }}</code></td>
                                        <td>{{ $product->quantity }}</td>
                                        <td>{{ $product->reorder_level }}</td>
                                        <td><span class="badge badge-{{ $product->quantity == 0 ? 'out' : 'low' }}">{{ $product->stock_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center py-4 text-muted">All products are above reorder level.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cash-coin text-success me-2"></i> Inventory Valuation Report</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th class="ps-4">Category</th><th>Products</th><th>Units On Hand</th><th class="text-end pe-4">Inventory Value</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($valuation as $row)
                            <tr>
                                <td class="ps-4"><span class="badge badge-category">{{ $row->category }}</span></td>
                                <td>{{ $row->items }}</td>
                                <td>{{ $row->units }}</td>
                                <td class="text-end pe-4 fw-bold">₱{{ number_format($row->value, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center py-4 text-muted">No active products to value.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th class="ps-4">TOTAL</th><th>{{ $valuation->sum('items') }}</th><th>{{ $totalUnits }}</th>
                            <th class="text-end pe-4">₱{{ number_format($totalValue, 2) }}</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
@endsection
