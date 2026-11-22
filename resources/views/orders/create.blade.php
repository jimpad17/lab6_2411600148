@extends('layouts.app')

@section('title', 'New Order')

@section('heading')
    New <span class="text-warning fw-bold">Order</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 900px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-cart-plus text-success me-2"></i> Order Details</h5>
            <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Orders
            </a>
        </div>
        <div class="card-body p-4">
            <div class="alert alert-warning py-2 small">
                <i class="bi bi-info-circle me-1"></i>
                Orders are <strong>pending</strong> until an admin reviews them. Nothing changes in inventory yet.
            </div>

            <form method="POST" action="{{ route('orders.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Order Type <span class="text-danger">*</span></label>
                        <select name="type" id="type" class="form-select" required>
                            @foreach ($types as $value => $label)
                                <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold text-muted mb-1">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" value="{{ old('order_date', $defaultDate) }}"
                               min="{{ $defaultDate }}" class="form-control" required>
                        <small class="text-muted" style="font-size:.7rem;">Today or a future date only — past dates are rejected.</small>
                        @error('order_date') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted mb-1">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="productId" class="form-select" required>
                            <option value="">Select a product…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" data-stock="{{ (int) $product->quantity }}"
                                        data-brand="{{ $product->brand?->name }}" data-category="{{ $product->category?->name }}"
                                        @selected((string) old('product_id') === (string) $product->id)>
                                    {{ $product->name }} — {{ $product->sku }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Current Stock</label>
                        <input type="text" id="stockPreview" class="form-control" value="Select a product" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}"
                               min="1" max="1000000" class="form-control" required>
                        @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                               maxlength="150" class="form-control" placeholder="PO-2026-001 / SO-2026-004">
                        @error('reference_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Brand / Category</label>
                        <input type="text" id="productMeta" class="form-control" value="—" readonly>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted mb-1">Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                                  placeholder="Why is this order needed?">{{ old('notes') }}</textarea>
                        @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-send me-1"></i> Submit for Approval
                    </button>
                    <a href="{{ route('orders.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const select = document.getElementById('productId');
            const preview = document.getElementById('stockPreview');
            const meta = document.getElementById('productMeta');

            function refresh() {
                const option = select.options[select.selectedIndex];
                if (!option || !option.value) {
                    preview.value = 'Select a product';
                    meta.value = '—';
                    return;
                }
                preview.value = option.dataset.stock + ' pcs on hand';
                meta.value = [option.dataset.brand, option.dataset.category].filter(Boolean).join(' / ') || '—';
            }

            select.addEventListener('change', refresh);
            refresh();
        })();
    </script>
@endpush
