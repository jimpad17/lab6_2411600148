<div class="row g-3">
    <div class="col-md-7">
        <label for="name" class="form-label small fw-bold text-muted">Product Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $product->name) }}" required>
        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-5">
        <label for="sku" class="form-label small fw-bold text-muted">SKU <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku', $product->sku) }}" placeholder="JT-EQP-000" required>
        @error('sku') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label for="description" class="form-label small fw-bold text-muted">Description</label>
        <textarea class="form-control" id="description" name="description" rows="2" placeholder="Optional product details">{{ old('description', $product->description) }}</textarea>
        @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="brand_id" class="form-label small fw-bold text-muted">Brand <span class="text-danger">*</span></label>
        <select class="form-select" id="brand_id" name="brand_id" required>
            <option value="">Choose brand...</option>
            @foreach ($brands as $brand)
                <option value="{{ $brand->id }}" {{ (string) old('brand_id', $product->brand_id) === (string) $brand->id ? 'selected' : '' }}>{{ $brand->name }}</option>
            @endforeach
        </select>
        @error('brand_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-6">
        <label for="category_id" class="form-label small fw-bold text-muted">Category <span class="text-danger">*</span></label>
        <select class="form-select" id="category_id" name="category_id" required>
            <option value="">Choose category...</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" {{ (string) old('category_id', $product->category_id) === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
            @endforeach
        </select>
        @error('category_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label for="quantity" class="form-label small fw-bold text-muted">Quantity <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="quantity" name="quantity" min="0" value="{{ old('quantity', $product->quantity) }}" required>
        @error('quantity') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label for="reorder_level" class="form-label small fw-bold text-muted">Reorder Level <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="reorder_level" name="reorder_level" min="0" value="{{ old('reorder_level', $product->reorder_level ?? 5) }}" required>
        @error('reorder_level') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-3">
        <label for="unit_price" class="form-label small fw-bold text-muted">Unit Price (₱) <span class="text-danger">*</span></label>
        <input type="number" class="form-control" id="unit_price" name="unit_price" min="0.01" step="0.01" value="{{ old('unit_price', $product->unit_price) }}" required>
        @error('unit_price') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label for="supplier" class="form-label small fw-bold text-muted">Supplier</label>
        <input type="text" class="form-control" id="supplier" name="supplier" value="{{ old('supplier', $product->supplier) }}" placeholder="Optional supplier name">
        @error('supplier') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1"
                   {{ old('is_active', $product->exists ? $product->is_active : true) ? 'checked' : '' }}>
            <label class="form-check-label small fw-bold text-muted" for="is_active">
                Available (uncheck to mark the product unavailable without deleting it)
            </label>
        </div>
    </div>
</div>
