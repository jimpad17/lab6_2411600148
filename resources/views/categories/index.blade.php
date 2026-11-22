@extends('layouts.app')

@section('title', 'Categories')

@section('heading')
    Product <span class="text-warning fw-bold">Categories</span>
@endsection

@section('content')
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <form method="GET" action="{{ route('categories.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search category..." value="{{ $filters['search'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted mb-1">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All</option>
                        <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm px-3"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-sm px-3">Clear</a>
                </div>
                <div class="col-md-5 text-md-end">
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('categories.create') }}" class="btn btn-success btn-sm px-3">
                            <i class="bi bi-plus-circle me-1"></i> Add Category
                        </a>
                    @endif
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-tags text-warning me-2"></i> Category List
                <small class="text-muted fw-normal ms-2">{{ $categories->total() }} records</small>
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-4">Category Name</th>
                            <th>Slug</th>
                            <th>Description</th>
                            <th>Active Products</th>
                            <th>Status</th>
                            <th>Date Added</th>
                            <th class="text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="{{ $category->is_active ? '' : 'table-inactive' }}">
                                <td class="ps-4"><strong>{{ $category->name }}</strong></td>
                                <td><code class="sku-tag">{{ $category->slug }}</code></td>
                                <td><small class="text-muted">{{ $category->description ?: '—' }}</small></td>
                                <td><span class="badge bg-primary">{{ $category->active_products_count }}</span></td>
                                <td>
                                    <span class="badge {{ $category->is_active ? 'badge-in' : 'badge-inactive' }}">
                                        {{ $category->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                                <td><small>{{ $category->created_at->format('M d, Y') }}</small></td>
                                <td class="text-end pe-4 text-nowrap">
                                    @if (Auth::user()->isAdmin())
                                        <a href="{{ route('categories.edit', $category) }}" class="btn btn-sm btn-outline-warning" title="Edit">
                                            <i class="bi bi-pencil-square"></i>
                                        </a>
                                        @if ($category->is_active)
                                            <form method="POST" action="{{ route('categories.destroy', $category) }}" class="d-inline"
                                                  onsubmit="return confirm('Set \'{{ addslashes($category->name) }}\' to inactive? Its products will be kept.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Deactivate">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('categories.restore', $category) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-outline-success" title="Restore">
                                                    <i class="bi bi-arrow-counterclockwise"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-muted small">View only</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $categories->links() }}</div>
    </div>
@endsection
