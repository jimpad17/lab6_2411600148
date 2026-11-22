@extends('layouts.app')

@section('title', 'Edit User')

@section('heading')
    Edit <span class="text-warning fw-bold">User</span>
@endsection

@section('content')
    <div class="card shadow-sm" style="max-width: 760px;">
        <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-warning me-2"></i> Update: {{ $user->name }}</h5>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i> Back</a>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label small fw-bold text-muted">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="email" class="form-label small fw-bold text-muted">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label small fw-bold text-muted">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role" name="role" required>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Admin</option>
                            <option value="staff" {{ old('role', $user->role) === 'staff' ? 'selected' : '' }}>Staff</option>
                        </select>
                        @error('role') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <div class="form-check mt-4 pt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}>
                            <label class="form-check-label small fw-bold text-muted" for="is_active">Active account</label>
                        </div>
                    </div>
                    <div class="col-12"><hr class="my-1"><small class="text-muted">Leave password blank to keep the current password.</small></div>
                    <div class="col-md-6">
                        <label for="password" class="form-label small fw-bold text-muted">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" autocomplete="new-password">
                        @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label small fw-bold text-muted">Confirm New Password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" autocomplete="new-password">
                    </div>
                </div>
                <div class="d-flex gap-2 mt-4">
                    <button type="submit" class="btn btn-primary px-4 fw-bold"><i class="bi bi-save me-1"></i> Update Account</button>
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
