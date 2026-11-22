@extends('layouts.app')

@section('title', 'Inventory')

@section('heading')
    Stock <span class="text-warning fw-bold">Overview</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Active Products</h6>
                    <h2 class="fw-bold text-primary mb-0">{{ $totalActive }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Units On Hand</h6>
                    <h2 class="fw-bold text-success mb-0">{{ number_format($unitsOnHand) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Stock Value</h6>
                    <h2 class="fw-bold text-warning mb-0" style="font-size: 1.6rem;">₱{{ number_format($stockValue, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Low / Out</h6>
                    <h2 class="fw-bold text-danger mb-0">{{ $lowCount }} / {{ $outCount }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('inventory.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Brand</label>
                    <select name="brand_id" class="form-select">
                        <option value="">All Brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" {{ (string) ($filters['brand_id'] ?? '') === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Category</label>
                    <select name="category_id" class="form-select">
                        <option value="">All Categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" {{ (string) ($filters['category_id'] ?? '') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Stock Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="in" {{ ($filters['status'] ?? '') === 'in' ? 'selected' : '' }}>In Stock</option>
                        <option value="low" {{ ($filters['status'] ?? '') === 'low' ? 'selected' : '' }}>Low Stock</option>
                        <option value="out" {{ ($filters['status'] ?? '') === 'out' ? 'selected' : '' }}>Out of Stock</option>
                        <option value="unavailable" {{ ($filters['status'] ?? '') === 'unavailable' ? 'selected' : '' }}>Unavailable</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search inventory..." value="{{ $filters['search'] ?? '' }}">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-search"></i></button>
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-data text-warning me-2"></i> Stock Levels &amp; Quick Adjustment</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Product</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>On Hand</th>
                            <th>Reorder At</th>
                            <th>Status</th>
                            <th>Value</th>
                            <th class="text-end pe-4">Quick Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="{{ $product->is_active ? '' : 'table-inactive' }}">
                                <td class="ps-4"><strong>{{ $product->name }}</strong><br><code class="sku-tag">{{ $product->sku }}</code></td>
                                <td><small>{{ $product->brand?->name ?? '—' }}</small></td>
                                <td><span class="badge badge-category">{{ $product->category?->name ?? '—' }}</span></td>
                                <td><strong>{{ $product->quantity }}</strong></td>
                                <td>{{ $product->reorder_level }}</td>
                                <td>
                                    <span class="badge badge-{{ $product->quantity == 0 ? 'out' : ($product->isLowStock() ? 'low' : 'in') }}{{ $product->is_active ? '' : ' badge-inactive' }}">
                                        {{ $product->stock_status }}
                                    </span>
                                </td>
                                <td>₱{{ number_format($product->stock_value, 2) }}</td>
                                <td class="text-end pe-4">
                                    @if ($product->is_active)
                                        <form method="POST" action="{{ route('inventory.stock', $product) }}" class="d-flex justify-content-end gap-1">
                                            @csrf
                                            <input type="number" name="quantity" value="1" min="1" class="form-control form-control-sm" style="width: 70px;" aria-label="Quantity">
                                            <button type="submit" name="type" value="in" class="btn btn-sm btn-success" title="Stock In"><i class="bi bi-plus-lg"></i> In</button>
                                            <button type="submit" name="type" value="out" class="btn btn-sm btn-warning" title="Stock Out"><i class="bi bi-dash-lg"></i> Out</button>
                                        </form>
                                    @else
                                        <small class="text-muted">Unavailable</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">No stock records match your filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $products->links() }}</div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-success me-2"></i> Recent Stock Movements</h5>
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
                            <tr><td colspan="6" class="text-center text-muted py-4">No stock movements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
