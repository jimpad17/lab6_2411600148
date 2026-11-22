@extends('layouts.app')

@section('title', 'Stock Adjustment')

@section('heading')
    Stock <span class="text-warning fw-bold">Adjustment</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 900px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-sliders2 text-warning me-2"></i> Physical Count Adjustment</h5>
            <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i> Back to Transactions
            </a>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small">
                <i class="bi bi-info-circle me-1"></i>
                Enter the quantity counted on the shelf. JimTrack calculates the difference, records an
                adjustment transaction and updates the stock level with your name attached to the change.
            </p>

            <form method="POST" action="{{ route('adjustments.store') }}">
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
                        <label class="form-label small fw-bold text-muted mb-1">System Quantity (read-only)</label>
                        <input type="text" id="systemQuantity" class="form-control" value="Select a product" readonly>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Physical Count <span class="text-danger">*</span></label>
                        <input type="number" name="physical_count" id="physicalCount" value="{{ old('physical_count') }}"
                               min="0" max="1000000" class="form-control" placeholder="Units counted" required>
                        @error('physical_count') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Calculated Difference</label>
                        <input type="text" id="differencePreview" class="form-control fw-bold" value="—" readonly>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold text-muted mb-1">Reason <span class="text-danger">*</span></label>
                        <select name="reason" class="form-select" required>
                            <option value="">Choose a reason…</option>
                            @foreach ($reasons as $value => $label)
                                <option value="{{ $value }}" @selected(old('reason') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('reason') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label small fw-bold text-muted mb-1">Notes</label>
                        <textarea name="notes" rows="2" maxlength="1000" class="form-control"
                                  placeholder="e.g. Counted during monthly stock take, 2 boxes damaged.">{{ old('notes') }}</textarea>
                        @error('notes') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-warning px-4 fw-bold">
                        <i class="bi bi-save me-1"></i> Apply Adjustment
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
            const select = document.getElementById('productId');
            const system = document.getElementById('systemQuantity');
            const physical = document.getElementById('physicalCount');
            const preview = document.getElementById('differencePreview');
            let onHand = null;

            function updateDifference() {
                if (onHand === null || physical.value === '') {
                    preview.value = '—';
                    preview.className = 'form-control fw-bold';
                    return;
                }

                const difference = Number(physical.value) - onHand;
                preview.value = (difference > 0 ? '+' : '') + difference + ' pcs';
                preview.className = 'form-control fw-bold ' + (difference > 0 ? 'text-success' : difference < 0 ? 'text-danger' : 'text-muted');
            }

            select.addEventListener('change', function () {
                onHand = null;
                system.value = 'Loading…';
                updateDifference();

                if (!select.value) {
                    system.value = 'Select a product';
                    return;
                }

                fetch('{{ url("api/products") }}/' + select.value + '/stock', { credentials: 'same-origin' })
                    .then(response => {
                        if (!response.ok) throw new Error('Unable to load stock');
                        return response.json();
                    })
                    .then(data => {
                        onHand = data.data.quantity;
                        system.value = onHand + ' pcs (reorder at ' + data.data.reorder_level + ')';
                        updateDifference();
                    })
                    .catch(() => {
                        system.value = 'Unavailable — try again';
                    });
            });

            physical.addEventListener('input', updateDifference);
            @if (old('product_id'))
                select.value = '{{ old("product_id") }}';
                select.dispatchEvent(new Event('change'));
            @endif
        })();
    </script>
@endpush
