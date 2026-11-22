<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Login') - JimTrack Fitness</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/jimtrack.css') }}">
</head>
<body class="login-page d-flex justify-content-center align-items-center vh-100">

    <div class="card shadow-lg login-card" style="max-width: 400px; width: 100%;">
        <!-- Lab 4 dark charcoal + gold branding -->
        <div class="card-header text-center py-4 bg-dark-navbar text-white">
            <h2 class="mb-0 fw-bold">
                <span>JimTrack Fitness</span>
            </h2>
        </div>

        <div class="card-body p-4 bg-white">
            @if (session('status'))
                <div class="alert alert-success py-2 text-center small fw-bold mb-3" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <div id="errorAlert" class="alert alert-danger {{ $errors->any() ? '' : 'd-none' }} py-2 text-center small fw-bold mb-3" role="alert">
                @if ($errors->any())
                    {{ $errors->first() }}
                @endif
            </div>

            {{ $slot }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
