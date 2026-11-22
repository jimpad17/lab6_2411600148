@extends('layouts.app')

@section('title', 'Add Category')

@section('heading')
    Add <span class="text-warning fw-bold">Category</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 760px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-tags text-success me-2"></i> New Category</h5>
            <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('categories.store') }}">
                @csrf
                @include('categories._form')
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-success px-4 fw-bold"><i class="bi bi-save me-1"></i> Save Category</button>
                    <a href="{{ route('categories.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
