@extends('layouts.app')

@section('title', 'Product Details')

@section('heading')
    Product <span class="text-warning fw-bold">Details</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">{{ $product->name }}</h5>
                    <span class="badge badge-{{ $product->quantity == 0 ? 'out' : ($product->isLowStock() ? 'low' : 'in') }}{{ $product->is_active ? '' : ' badge-inactive' }}">
                        {{ $product->stock_status }}
                    </span>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-md-6"><span class="text-muted d-block">SKU</span><code class="sku-tag">{{ $product->sku }}</code></div>
                        <div class="col-md-6"><span class="text-muted d-block">Brand</span><strong>{{ $product->brand?->name ?? '—' }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Category</span><span class="badge badge-category">{{ $product->category?->name ?? '—' }}</span></div>
                        <div class="col-md-6"><span class="text-muted d-block">Supplier</span><strong>{{ $product->supplier ?? '—' }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Quantity on Hand</span><strong>{{ $product->quantity }} pcs</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Reorder Level</span><strong>{{ $product->reorder_level }} pcs</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Unit Price</span><strong>₱{{ number_format($product->unit_price, 2) }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Stock Value</span><strong class="text-success">₱{{ number_format($product->stock_value, 2) }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Last Updated</span><strong>{{ $product->updated_at->format('M d, Y h:i A') }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block">Description</span><span>{{ $product->description ?: '—' }}</span></div>
                    </div>

                    <div class="d-flex gap-2 mt-4 flex-wrap">
                        @if (Auth::user()->isAdmin())
                            <a href="{{ route('products.edit', $product) }}" class="btn btn-warning px-4 fw-bold">
                                <i class="bi bi-pencil-square me-1"></i> Edit
                            </a>
                            @if ($product->is_active)
                                <form method="POST" action="{{ route('products.destroy', $product) }}"
                                      onsubmit="return confirm('Mark this product as unavailable? No records will be deleted.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger px-4">
                                        <i class="bi bi-x-circle me-1"></i> Mark Unavailable
                                    </button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('products.restore', $product) }}">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-success px-4">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Restore
                                    </button>
                                </form>
                            @endif
                        @endif
                        <a href="{{ route('transactions.create') }}" class="btn btn-outline-primary px-4">
                            <i class="bi bi-plus-circle me-1"></i> Record Movement
                        </a>
                        <a href="{{ route('inventory.index') }}" class="btn btn-outline-warning px-4">
                            <i class="bi bi-clipboard-data me-1"></i> Stock Overview
                        </a>
                        <a href="{{ route('products.index') }}" class="btn btn-outline-secondary px-4">Back to Products</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card stat-card shadow-sm text-center py-4 h-100">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold mb-3">Stock Health</h6>
                    @if (! $product->is_active)
                        <i class="bi bi-slash-circle text-secondary" style="font-size: 3rem;"></i>
                        <p class="fw-bold text-secondary mt-3">Unavailable / Inactive</p>
                    @elseif ($product->quantity == 0)
                        <i class="bi bi-exclamation-octagon-fill text-danger" style="font-size: 3rem;"></i>
                        <p class="fw-bold text-danger mt-3">Out of stock</p>
                    @elseif ($product->isLowStock())
                        <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                        <p class="fw-bold text-warning-dark mt-3">Reorder soon</p>
                    @else
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        <p class="fw-bold text-success mt-3">Healthy stock level</p>
                    @endif
                    <small class="text-muted">Threshold: {{ $product->reorder_level }} pcs</small>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-primary me-2"></i> Transaction History</h5>
            <form method="GET" action="{{ route('products.show', $product) }}" class="d-flex align-items-end gap-2 flex-wrap">
                <div>
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: .7rem;">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div>
                    <label class="form-label small fw-bold text-muted mb-1" style="font-size: .7rem;">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-funnel"></i></button>
                <a href="{{ route('products.show', $product) }}" class="btn btn-outline-secondary btn-sm">Clear</a>
            </form>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th class="ps-4">Date / Time</th><th>Type</th><th>Quantity</th><th>Running Balance</th><th>Reference</th><th>User</th></tr>
                    </thead>
                    <tbody>
                        @php $computedBalance = 0; @endphp
                        @forelse ($transactions as $transaction)
                            @php $computedBalance = $transaction->balance_after ?? ($computedBalance + $transaction->signedQuantity()); @endphp
                            <tr class="{{ $transaction->is_reversed ? 'table-inactive' : '' }}">
                                <td class="ps-4 text-nowrap">{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                <td>
                                    <span class="badge {{ $transaction->badge_class }}">{{ $transaction->type_label }}</span>
                                    @if ($transaction->is_reversed)<span class="badge bg-secondary ms-1">Reversed</span>@endif
                                </td>
                                <td class="fw-bold {{ $transaction->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity_display }}</td>
                                <td><strong>{{ $computedBalance }}</strong></td>
                                <td><code>{{ $transaction->reference_number ?? '—' }}</code></td>
                                <td>{{ $transaction->user?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No stock movements in this date range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">
            <small class="text-muted">Running balance is the quantity remaining after each recorded movement.</small>
        </div>
    </div>
@endsection
