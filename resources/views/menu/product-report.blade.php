@extends('layouts.app')

@section('title', 'Product Report')

@section('heading')
    Product <span class="text-warning fw-bold">Report</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Products</h6>
                <h2 class="fw-bold text-primary mb-0">{{ $totalProducts }}</h2>
                <small class="text-muted">Catalog records</small>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Sold</h6>
                <h2 class="fw-bold text-success mb-0">{{ number_format($totalSales) }}</h2>
                <small class="text-muted">Approved sales orders</small>
            </div></div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="card stat-card shadow-sm text-center py-3"><div class="card-body py-2">
                <h6 class="card-title text-uppercase fw-bold mb-2">Units Purchased</h6>
                <h2 class="fw-bold text-warning mb-0">{{ number_format($totalPurchases) }}</h2>
                <small class="text-muted">Approved purchase orders</small>
            </div></div>
        </div>
    </div>

    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('product-report') }}" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" class="form-control form-control-sm" placeholder="Name or SKU">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Brand</label>
                    <select name="brand_id" class="form-select form-select-sm">
                        <option value="">All brands</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((string) ($filters['brand_id'] ?? '') === (string) $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Category</label>
                    <select name="category_id" class="form-select form-select-sm">
                        <option value="">All categories</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('product-report') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam text-warning me-2"></i> Product Summary</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th class="ps-4">Product</th><th>Brand / Category</th><th>On Hand</th><th>Reorder</th><th>Sales Orders</th><th>Purchase Orders</th><th>Status</th><th class="text-end pe-4">Action</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            <tr class="{{ $product->is_active ? '' : 'table-inactive' }}">
                                <td class="ps-4"><strong>{{ $product->name }}</strong><br><code class="sku-tag">{{ $product->sku }}</code></td>
                                <td><small>{{ $product->brand?->name ?? '—' }} / {{ $product->category?->name ?? '—' }}</small></td>
                                <td class="fw-bold">{{ $product->quantity }}</td>
                                <td>{{ $product->reorder_level }}</td>
                                <td>{{ $product->sales_orders_count }}</td>
                                <td>{{ $product->purchase_orders_count }}</td>
                                <td><span class="badge badge-{{ $product->quantity == 0 ? 'out' : ($product->isLowStock() ? 'low' : 'in') }}">{{ $product->stock_status }}</span></td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('products.show', $product) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center py-4 text-muted">No products match your filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $products->links() }}</div>
    </div>
@endsection
