<x-guest-layout>
    <h5 class="fw-bold mb-3 text-center text-dark">Choose a new password</h5>

    <form method="POST" action="{{ route('password.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-muted">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $request->email) }}" required readonly>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-bold text-muted">New Password</label>
            <input type="password" class="form-control" id="password" name="password" required autofocus autocomplete="new-password">
            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label small fw-bold text-muted">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-login-orange text-white w-100 py-2 fw-bold">Reset Password</button>
    </form>
</x-guest-layout>
