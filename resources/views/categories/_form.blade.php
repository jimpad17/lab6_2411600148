<div class="row g-3">
    <div class="col-md-6">
        <label for="name" class="form-label small fw-bold text-muted">Category Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $category->name) }}" required>
        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label for="slug" class="form-label small fw-bold text-muted">Slug <small class="text-muted fw-normal">(auto-generated if blank)</small></label>
        <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $category->slug) }}">
        @error('slug') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label for="description" class="form-label small fw-bold text-muted">Description</label>
        <textarea class="form-control" id="description" name="description" rows="3">{{ old('description', $category->description) }}</textarea>
        @error('description') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
    </div>
    @if ($category->exists)
        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $category->is_active) ? 'checked' : '' }}>
                <label class="form-check-label small fw-bold text-muted" for="is_active">Active category</label>
            </div>
        </div>
    @endif
</div>
