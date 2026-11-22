<x-guest-layout>
    <h5 class="fw-bold mb-2 text-center text-dark">Reset your password</h5>
    <p class="small text-muted text-center mb-4">
        Enter your email and we will send you a password reset link.
    </p>

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <div class="mb-4">
            <label for="email" class="form-label small fw-bold text-muted">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-login-orange text-white w-100 py-2 fw-bold">
            {{ __('Email Password Reset Link') }}
        </button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none text-muted small fw-semibold">Back to login</a>
        </div>
    </form>
</x-guest-layout>
