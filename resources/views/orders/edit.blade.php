@extends('layouts.app')

@section('title', 'Edit Order '.$order->order_number)

@section('heading')
    Review &amp; Correct <span class="text-warning fw-bold">Order</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 900px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> {{ $order->order_number }}</h5>
            <a href="{{ route('orders.show', $order) }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Order
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning py-2 small">
                <i class="bi bi-shield-check me-1"></i>
                Fix the staff mistake here. The stock is only updated when you approve the corrected order.
            </div>

            @if ($order->was_edited)
                <div class="alert alert-info py-2 small">
                    Already corrected once: {{ $order->original_quantity }} pcs → {{ $order->quantity }} pcs.
                </div>
            @endif

            <form method="POST" action="{{ route('orders.update', $order) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Order Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type', $order->type) === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', $order->order_date->toDateString()) }}"
                               min="{{ today()->toDateString() }}" class="form-control" required>
                        @error('order_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted mb-1">Product <span class="text-danger">*</span></label>
                        <select name="product_id" class="form-select" required>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected((string) old('product_id', $order->product_id) === (string) $product->id)>
                                    {{ $product->name }} — {{ $product->sku }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Quantity on Hand</label>
                        <input type="text" class="form-control" value="{{ $order->product?->quantity ?? 0 }} pcs" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Corrected Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" value="{{ old('quantity', $order->quantity) }}"
                               min="1" max="1000000" class="form-control" required>
                        <small class="text-muted">Originally submitted: {{ $order->quantity }} pcs</small>
                        @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number', $order->reference_number) }}"
                               maxlength="150" class="form-control">
                        @error('reference_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Submitted By</label>
                        <input type="text" class="form-control" value="{{ $order->creator?->name ?? 'System' }}" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted mb-1">Notes / Correction Reason</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="form-control">{{ old('notes', $order->notes) }}</textarea>
                        @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-warning px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Save Correction
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
