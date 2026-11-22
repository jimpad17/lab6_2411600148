@extends('layouts.app')

@section('title', 'Inventory Reports')

@section('heading')
    Inventory <span class="text-warning fw-bold">Reports</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Active Products</h6>
                    <h2 class="fw-bold text-primary mb-0">{{ $totalItems }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Total Value</h6>
                    <h2 class="fw-bold text-success mb-0" style="font-size: 1.6rem;">₱{{ number_format($totalValue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Categories</h6>
                    <h2 class="fw-bold text-warning mb-0">{{ $categories->count() }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Reorder Needed</h6>
                    <h2 class="fw-bold text-danger mb-0">{{ $lowStockProducts->count() }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="{{ route('dashboard') }}" class="small text-decoration-none fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
        </a>
        <a href="{{ route('reports.export') }}" class="btn btn-primary btn-sm px-3">
            <i class="bi bi-download me-1"></i> Download Category Report (CSV)
        </a>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-grid-1x2-fill text-primary me-2"></i> Valuation by Category</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">Category</th><th>Products</th><th>Units</th><th class="text-end pe-3">Value</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $row)
                                    <tr>
                                        <td class="ps-3"><span class="badge badge-category">{{ $row->category }}</span></td>
                                        <td>{{ $row->items }}</td>
                                        <td>{{ $row->units }}</td>
                                        <td class="text-end pe-3 fw-bold">₱{{ number_format($row->value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No products to report.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-award text-warning me-2"></i> Valuation by Brand</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">Brand</th><th>Products</th><th>Units</th><th class="text-end pe-3">Value</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($brands as $row)
                                    <tr>
                                        <td class="ps-3"><strong>{{ $row->brand }}</strong></td>
                                        <td>{{ $row->items }}</td>
                                        <td>{{ $row->units }}</td>
                                        <td class="text-end pe-3 fw-bold">₱{{ number_format($row->value, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">No brands to report.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-7 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i> Reorder List</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">Product</th><th>On Hand</th><th>Reorder At</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($lowStockProducts as $item)
                                    <tr>
                                        <td class="ps-3"><strong>{{ $item->name }}</strong></td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>{{ $item->reorder_level }}</td>
                                        <td><span class="badge badge-{{ $item->quantity == 0 ? 'out' : 'low' }}">{{ $item->stock_status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center py-4 text-muted">Nothing needs reordering.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-archive text-secondary me-2"></i> Unavailable Products (Kept)</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">Product</th><th>Brand</th><th>Stock</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($inactiveProducts as $item)
                                    <tr class="table-inactive">
                                        <td class="ps-3"><strong>{{ $item->name }}</strong></td>
                                        <td><small>{{ $item->brand?->name ?? '—' }}</small></td>
                                        <td>{{ $item->quantity }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center py-4 text-muted">No inactive products.</td></tr>
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
            <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-success me-2"></i> Stock Movement Log</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th class="ps-3">Product</th><th>Type</th><th>Qty</th><th>Reference</th><th>User</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTransactions as $transaction)
                            <tr>
                                <td class="ps-3">{{ $transaction->product?->name ?? 'Deleted product' }}</td>
                                <td><span class="badge {{ $transaction->badge_class }}">{{ $transaction->type_label }}</span></td>
                                <td class="fw-bold {{ $transaction->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity_display }}</td>
                                <td><code>{{ $transaction->reference_number ?? '—' }}</code></td>
                                <td>{{ $transaction->user?->name ?? 'System' }}</td>
                                <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No stock movements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
