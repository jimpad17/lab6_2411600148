@extends('layouts.app')

@section('title', 'Product History')

@section('heading')
    Product <span class="text-warning fw-bold">History</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-4 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Products Tracked</h6>
            <h2 class="fw-bold text-primary mb-0">{{ $products->total() }}</h2>
        </div></div></div>
        <div class="col-md-4 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Stock Movements</h6>
            <h2 class="fw-bold text-warning mb-0">{{ number_format($movementCount) }}</h2>
        </div></div></div>
        <div class="col-md-4 mb-3"><div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
            <h6 class="card-title text-uppercase fw-bold mb-2">Recent Movements</h6>
            <h2 class="fw-bold text-success mb-0">{{ $recent->count() }}</h2>
        </div></div></div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-warning me-2"></i> Latest Stock Movements</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Reference</th><th>Product</th><th>Type</th><th>Qty</th><th>Performed By</th><th>Date / Time</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $movement)
                            <tr>
                                <td class="ps-4"><code>{{ $movement->reference_number }}</code></td>
                                <td>{{ $movement->product?->name ?? 'Deleted product' }}</td>
                                <td><span class="badge text-bg-{{ $movement->type === 'in' ? 'success' : ($movement->type === 'out' ? 'danger' : 'secondary') }}">{{ strtoupper($movement->type) }}</span></td>
                                <td class="fw-bold">{{ $movement->quantity }}</td>
                                <td>{{ $movement->user?->name ?? '—' }}</td>
                                <td>{{ $movement->created_at->format('M d, Y h:i A') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-3 text-muted">No movements recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('product-history') }}" class="row g-3 align-items-end">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="Product name or SKU">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('product-history') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam text-primary me-2"></i> Per-Product Movement Counts</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Product</th><th>SKU</th><th>Brand / Category</th><th>Stock In</th><th>Stock Out</th><th>Total</th><th>On Hand</th></tr></thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr>
                                <td class="ps-4 fw-bold">{{ $product->name }}</td>
                                <td><code class="sku-tag">{{ $product->sku }}</code></td>
                                <td><small>{{ $product->brand?->name ?? '—' }} / {{ $product->category?->name ?? '—' }}</small></td>
                                <td class="text-success fw-bold">{{ $product->stock_in_count }}</td>
                                <td class="text-danger fw-bold">{{ $product->stock_out_count }}</td>
                                <td class="fw-bold">{{ $product->transactions_count }}</td>
                                <td>{{ $product->quantity }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center py-4 text-muted">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $products->links() }}</div>
    </div>
@endsection
