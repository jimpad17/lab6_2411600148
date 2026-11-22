@extends('layouts.app')

@section('title', 'Transaction #'.$transaction->id)

@section('heading')
    Transaction <span class="text-warning fw-bold">Details</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-lg-6 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-warning me-2"></i> Transaction #{{ $transaction->id }}</h5>
                    <span class="badge {{ $transaction->badge_class }}">{{ $transaction->type_label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-md-6"><span class="text-muted d-block">Quantity Moved</span>
                            <strong class="{{ $transaction->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity_display }} pcs</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Balance After</span>
                            <strong>{{ $transaction->balance_after ?? '—' }} pcs</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Reference Number</span>
                            <code>{{ $transaction->reference_number ?? '—' }}</code></div>
                        <div class="col-md-6"><span class="text-muted d-block">Date / Time</span>
                            <strong>{{ $transaction->created_at->format('M d, Y h:i A') }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block">Notes</span>
                            <span>{{ $transaction->notes ?: 'No additional notes.' }}</span></div>
                        @if ($transaction->is_reversed)
                            <div class="col-12"><span class="badge bg-secondary">This movement has been reversed</span></div>
                        @endif
                    </div>
                    <div class="d-flex gap-2 mt-4 flex-wrap">
                        <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                        @if (Auth::user()->isAdmin() && ! $transaction->is_reversed)
                            <form method="POST" action="{{ route('transactions.destroy', $transaction) }}"
                                  onsubmit="return confirm('Reverse this movement? The original record is kept for auditing.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger px-4">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Reverse Movement
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-3">
            <div class="card stat-card shadow-sm text-center h-100 py-4">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold mb-3">Product</h6>
                    <i class="bi bi-box-seam text-warning" style="font-size: 2.4rem;"></i>
                    <p class="fw-bold mt-3 mb-1">{{ $transaction->product?->name ?? 'Deleted product' }}</p>
                    @if ($transaction->product)
                        <code class="sku-tag">{{ $transaction->product->sku }}</code>
                        <div class="text-muted small mt-2">On hand: {{ $transaction->product->quantity }} pcs</div>
                        <a href="{{ route('products.show', $transaction->product) }}" class="small text-decoration-none fw-semibold">
                            Open product →
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-3 mb-3">
            <div class="card stat-card shadow-sm text-center h-100 py-4">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold mb-3">Performed By</h6>
                    <i class="bi bi-person-badge text-primary" style="font-size: 2.4rem;"></i>
                    <p class="fw-bold mt-3 mb-1">{{ $transaction->user?->name ?? 'System' }}</p>
                    <span class="badge role-badge">{{ strtoupper($transaction->user?->role ?? 'system') }}</span>
                    <div class="text-muted small mt-2">Accountable for this movement</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i> Running Balance (This Product)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light">
                        <tr><th class="ps-4">Date</th><th>Type</th><th>Quantity</th><th>Balance After</th><th>Reference</th><th>User</th></tr>
                    </thead>
                    <tbody>
                        @php $computedBalance = 0; @endphp
                        @forelse ($history as $item)
                            @php $computedBalance = $item->balance_after ?? ($computedBalance + $item->signedQuantity()); @endphp
                            <tr class="{{ $item->id === $transaction->id ? 'table-warning' : '' }}">
                                <td class="ps-4 text-nowrap">{{ $item->created_at->format('M d, Y h:i A') }}</td>
                                <td><span class="badge {{ $item->badge_class }}">{{ $item->type_label }}</span></td>
                                <td class="fw-bold {{ $item->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $item->quantity_display }}</td>
                                <td><strong>{{ $computedBalance }}</strong></td>
                                <td><code>{{ $item->reference_number ?? '—' }}</code></td>
                                <td>{{ $item->user?->name ?? 'System' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center py-4 text-muted">No movements recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
