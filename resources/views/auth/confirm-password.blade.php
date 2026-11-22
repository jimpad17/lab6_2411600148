<x-guest-layout>
    <h5 class="fw-bold mb-2 text-center text-dark">Confirm your password</h5>
    <p class="small text-muted text-center mb-4">Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}">
        @csrf

        <div class="mb-4">
            <label for="password" class="form-label small fw-bold text-muted">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password">
            @error('password') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <button type="submit" class="btn btn-login-orange text-white w-100 py-2 fw-bold">Confirm Password</button>
    </form>
</x-guest-layout>
