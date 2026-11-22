<x-guest-layout>
    <h5 class="fw-bold mb-2 text-center text-dark">Verify your email address</h5>

    @if (session('status') == 'verification-link-sent')
        <div class="alert alert-success py-2 text-center small fw-bold">
            A new verification link has been sent to your email address.
        </div>
    @endif

    <p class="small text-muted text-center mb-4">
        Before continuing, please verify your email address using the link we sent you.
    </p>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-login-orange text-white w-100 py-2 fw-bold">
            Resend Verification Email
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-3 text-center">
        @csrf
        <button type="submit" class="btn btn-link text-muted small fw-semibold text-decoration-none">Log Out</button>
    </form>
</x-guest-layout>
