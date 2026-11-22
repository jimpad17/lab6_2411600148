@extends('layouts.app')

@section('title', 'Record Stock Movement')

@section('heading')
    Record Stock <span class="text-warning fw-bold">Movement</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 900px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-plus-circle text-success me-2"></i> New Transaction</h5>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Transactions
            </a>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('transactions.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label small fw-bold text-muted mb-1">Product <span class="text-danger">*</span></label>
                        <select name="product_id" id="productId" class="form-select" required>
                            <option value="">Select a product…</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected(old('product_id') == $product->id)>
                                    {{ $product->name }} — {{ $product->sku }}
                                </option>
                            @endforeach
                        </select>
                        @error('product_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted mb-1">Current Stock Level</label>
                        <div class="card stat-card shadow-sm h-100 py-2" id="stockPreview">
                            <div class="card-body py-1">
                                <h4 class="fw-bold text-primary mb-0" id="currentQuantity">Select a product</h4>
                                <small class="text-muted" id="reorderHint">Current on-hand quantity will appear here</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted mb-1">Transaction Type <span class="text-danger">*</span></label>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($types as $value => $label)
                                <div class="form-check form-check-inline border rounded px-3 py-2 mb-0">
                                    <input class="form-check-input" type="radio" name="type" id="type_{{ $value }}"
                                           value="{{ $value }}" @checked(old('type', 'in') === $value)>
                                    <label class="form-check-label small fw-bold" for="type_{{ $value }}">
                                        <i class="bi bi-{{ $value === 'in' ? 'box-arrow-in-down' : ($value === 'out' ? 'box-arrow-up-right' : 'sliders2') }} me-1"></i>
                                        {{ $label }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        @error('type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4" id="directionWrapper" style="display: none;">
                        <label class="form-label small fw-bold text-muted mb-1">Adjustment Direction <span class="text-danger">*</span></label>
                        <select name="direction" id="direction" class="form-select">
                            <option value="in" @selected(old('direction') === 'in')>Add stock (+)</option>
                            <option value="out" @selected(old('direction') === 'out')>Remove stock (−)</option>
                        </select>
                        @error('direction') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Quantity <span class="text-danger">*</span></label>
                        <input type="number" name="quantity" id="quantity" value="{{ old('quantity') }}"
                               min="1" max="1000000" class="form-control" placeholder="Number of units" required>
                        <small class="text-muted" id="quantityHint">Enter the number of units moved.</small>
                        @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Reference Number</label>
                        <input type="text" name="reference_number" value="{{ old('reference_number') }}"
                               maxlength="150" class="form-control" placeholder="PO-2026-001 / SO-2026-004">
                        @error('reference_number') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted mb-1">Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                                  placeholder="Why did this movement happen?">{{ old('notes') }}</textarea>
                        @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Record Movement
                    </button>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function () {
            const products = @json($productStocks);
            const select = document.getElementById('productId');
            const quantity = document.getElementById('quantity');
            const current = document.getElementById('currentQuantity');
            const reorderHint = document.getElementById('reorderHint');
            const quantityHint = document.getElementById('quantityHint');
            const directionWrapper = document.getElementById('directionWrapper');
            const typeRadios = document.querySelectorAll('input[name="type"]');
            const direction = document.getElementById('direction');

            function selectedType() {
                const checked = document.querySelector('input[name="type"]:checked');
                return checked ? checked.value : 'in';
            }

            function refresh() {
                const product = products.find(p => String(p.id) === String(select.value));

                if (!product) {
                    current.textContent = 'Select a product';
                    current.className = 'fw-bold text-primary mb-0';
                    reorderHint.textContent = 'Current on-hand quantity will appear here';
                    quantity.removeAttribute('max');
                    quantityHint.textContent = 'Enter the number of units moved.';
                } else {
                    current.textContent = product.quantity + ' pcs';
                    current.className = 'fw-bold ' + (product.quantity <= product.reorder ? 'text-warning' : 'text-success') + ' mb-0';
                    reorderHint.textContent = product.name + ' · reorder at ' + product.reorder + ' pcs';
                    quantityHint.textContent = 'On hand: ' + product.quantity + ' pcs';
                    quantity.setAttribute('max', selectedType() === 'out' || (selectedType() === 'adjustment' && direction.value === 'out') ? product.quantity : '1000000');
                }

                directionWrapper.style.display = selectedType() === 'adjustment' ? '' : 'none';
            }

            select.addEventListener('change', refresh);
            direction.addEventListener('change', refresh);
            typeRadios.forEach(radio => radio.addEventListener('change', refresh));

            @if (old('product_id'))
                select.value = '{{ old('product_id') }}';
                fetch('{{ url("api/products") }}/' + select.value + '/stock', { credentials: 'same-origin' })
                    .then(r => r.ok ? r.json() : null)
                    .then(data => {
                        if (data && data.data) {
                            const index = products.findIndex(p => String(p.id) === String(select.value));
                            if (index >= 0) products[index].quantity = data.data.quantity;
                            refresh();
                        }
                    })
                    .catch(() => {});
            @endif

            refresh();
        })();
    </script>
@endpush
