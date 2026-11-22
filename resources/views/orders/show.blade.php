@extends('layouts.app')

@section('title', 'Order '.$order->order_number)

@section('heading')
    Order <span class="text-warning fw-bold">Details</span>
@endsection

@section('content')
    <div class="row mb-4">
        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-cart3 text-warning me-2"></i> {{ $order->order_number }}</h5>
                    <span class="badge {{ $order->badge_class }}">{{ $order->status_label }}</span>
                </div>
                <div class="card-body">
                    <div class="row g-3 small">
                        <div class="col-md-6"><span class="text-muted d-block">Order Type</span>
                            <strong>{{ $order->type_label }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Order Date</span>
                            <strong>{{ $order->order_date->format('M d, Y') }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Quantity</span>
                            <strong class="text-primary">{{ $order->quantity }} pcs</strong>
                            @if ($order->was_edited)
                                <div class="text-muted"><span style="text-decoration: line-through;">{{ $order->original_quantity }}</span> corrected by {{ $order->editor?->name ?? 'admin' }}</div>
                            @endif
                        </div>
                        <div class="col-md-6"><span class="text-muted d-block">Estimated Value</span>
                            <strong>₱{{ number_format($order->total, 2) }}</strong></div>
                        <div class="col-md-6"><span class="text-muted d-block">Reference Number</span>
                            <code>{{ $order->reference_number ?? '—' }}</code></div>
                        <div class="col-md-6"><span class="text-muted d-block">Submitted By</span>
                            <strong>{{ $order->creator?->name ?? 'System' }}</strong></div>
                        <div class="col-12"><span class="text-muted d-block">Notes</span>
                            <span>{{ $order->notes ?: 'No notes.' }}</span></div>
                        @if ($order->status === 'approved')
                            <div class="col-12"><span class="text-muted d-block">Approved By</span>
                                <strong>{{ $order->approver?->name ?? 'System' }}</strong>
                                <span class="text-muted">on {{ $order->approved_at?->format('M d, Y h:i A') }}</span></div>
                        @endif
                    </div>

                    <div class="d-flex gap-2 mt-4 flex-wrap">
                        <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary px-4">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                        @if (Auth::user()->isAdmin() && $order->status === 'pending')
                            <a href="{{ route('orders.edit', $order) }}" class="btn btn-warning px-4">
                                <i class="bi bi-pencil me-1"></i> Correct Order
                            </a>
                            <form method="POST" action="{{ route('orders.approve', $order) }}"
                                  onsubmit="return confirm('Approve and post this order to inventory?');">
                                @csrf
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="bi bi-check2 me-1"></i> Approve
                                </button>
                            </form>
                            <form method="POST" action="{{ route('orders.reject', $order) }}"
                                  onsubmit="return confirm('Reject this order?');">
                                @csrf
                                <button type="submit" class="btn btn-outline-danger px-4">
                                    <i class="bi bi-x-lg me-1"></i> Reject
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 mb-3">
            <div class="card stat-card shadow-sm h-100 py-3">
                <div class="card-body">
                    <h6 class="card-title text-uppercase fw-bold mb-3">Product</h6>
                    <i class="bi bi-box-seam text-warning" style="font-size:2rem;"></i>
                    <p class="fw-bold mt-2 mb-1">{{ $order->product?->name ?? 'Deleted product' }}</p>
                    @if ($order->product)
                        <code class="sku-tag">{{ $order->product->sku }}</code>
                        <div class="text-muted small mt-2">{{ $order->product->brand?->name }} / {{ $order->product->category?->name }}</div>
                        <div class="text-muted small">On hand: {{ $order->product->quantity }} pcs</div>
                        <a href="{{ route('products.show', $order->product) }}" class="small fw-semibold text-decoration-none">
                            Open product →
                        </a>
                    @endif
                    <hr>
                    <h6 class="fw-bold small text-uppercase text-muted">Workflow</h6>
                    <ol class="small mb-0">
                        <li class="{{ $order->status === 'pending' ? 'fw-bold text-warning' : '' }}">Submitted by staff</li>
                        <li class="{{ $order->status === 'approved' ? 'fw-bold text-success' : '' }}">Admin review / correction</li>
                        <li class="{{ $order->status === 'approved' ? 'fw-bold text-success' : '' }}">Posted to stock + audit trail</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
@endsection
