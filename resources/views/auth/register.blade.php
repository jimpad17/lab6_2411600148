<x-guest-layout>
    <h5 class="fw-bold mb-3 text-center text-dark">Create an account</h5>

    <form method="POST" action="{{ route('register') }}">
        @csrf

        <div class="mb-3">
            <label for="name" class="form-label small fw-bold text-muted">Name</label>
            <input type="text" class="form-control" id="name" name="name" value="{{ old('name') }}" required autofocus>
            @error('name') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label small fw-bold text-muted">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
            @error('email') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-3">
            <label for="password" class="form-label small fw-bold text-muted">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="new-password">
            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="mb-4">
            <label for="password_confirmation" class="form-label small fw-bold text-muted">Confirm Password</label>
            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password">
        </div>

        <button type="submit" class="btn btn-login-orange text-white w-100 py-2 fw-bold">Register</button>

        <div class="text-center mt-3">
            <a href="{{ route('login') }}" class="text-decoration-none text-muted small fw-semibold">
                Already registered? Log in
            </a>
        </div>
    </form>
</x-guest-layout>
