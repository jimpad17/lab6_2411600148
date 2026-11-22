<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') - JimTrack Fitness</title>

    <!-- Bootstrap 5 CSS (same UI toolkit used in Laboratory Exercise 4) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/jimtrack.css') }}">
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm py-2">
        <div class="container-fluid px-3">
            <a class="navbar-brand fw-bold text-white" href="{{ route('dashboard') }}">
                <i class="bi bi-lightning-charge-fill text-warning me-2"></i>JimTrack Fitness
                <span class="text-warning small ms-1">Inventory</span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                @php
                    $alertTypes = [
                        \App\Notifications\LowStockNotification::class,
                        \App\Notifications\StockOutNotification::class,
                    ];
                    $unreadAlertsCount = Auth::user()->unreadNotifications()
                        ->whereIn('type', $alertTypes)->count();
                    $unreadAlerts = Auth::user()->unreadNotifications()
                        ->whereIn('type', $alertTypes)
                        ->latest()->limit(5)->get();
                @endphp
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown me-3">
                        <a class="nav-link text-white position-relative" href="#" id="alertsDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false"
                           title="Inventory alerts">
                            <i class="bi bi-bell-fill text-warning" style="font-size: 1.15rem;"></i>
                            @if ($unreadAlertsCount > 0)
                                <span class="notification-count">{{ $unreadAlertsCount > 9 ? '9+' : $unreadAlertsCount }}</span>
                            @endif
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm notification-menu" aria-labelledby="alertsDropdown">
                            <li>
                                <h6 class="dropdown-header fw-bold">
                                    <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
                                    Inventory Alerts
                                    <span class="badge badge-low ms-1">{{ $unreadAlertsCount }}</span>
                                </h6>
                            </li>
                            @forelse ($unreadAlerts as $alert)
                                <li>
                                    <a class="dropdown-item small py-2" href="{{ $alert->data['url'] ?? route('notifications.index') }}">
                                        <div class="fw-bold text-dark">{{ $alert->data['product_name'] ?? 'Stock alert' }}</div>
                                        <div class="text-muted">{{ \Illuminate\Support\Str::limit($alert->data['message'] ?? '', 70) }}</div>
                                        <div class="text-muted" style="font-size: .68rem;">{{ $alert->created_at->diffForHumans() }}</div>
                                    </a>
                                </li>
                            @empty
                                <li><span class="dropdown-item-text small text-muted">No unread alerts. Stock levels look good.</span></li>
                            @endforelse
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small fw-semibold text-warning-dark" href="{{ route('notifications.index') }}">
                                <i class="bi bi-list-ul me-1"></i> View all alerts
                            </a></li>
                            @if ($unreadAlertsCount > 0)
                                <li>
                                    <form method="POST" action="{{ route('notifications.read-all') }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="dropdown-item small fw-semibold">
                                            <i class="bi bi-check2-all me-1"></i> Mark all as read
                                        </button>
                                    </form>
                                </li>
                            @endif
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white small" href="#" id="userDropdown"
                           role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle text-warning me-1"></i>{{ Auth::user()->name }}
                            <span class="badge role-badge ms-1">{{ strtoupper(Auth::user()->role) }}</span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="userDropdown">
                            <li><span class="dropdown-item-text small text-muted">{{ Auth::user()->email }}</span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item small fw-semibold">
                                        <i class="bi bi-box-arrow-right me-1"></i> Logout
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar Navigation -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar vh-100 p-3">
                <div class="position-sticky pt-2 d-flex flex-column" style="min-height: calc(100vh - 90px);">
                    <small class="text-uppercase text-muted fw-bold px-3 mb-2" style="font-size: .68rem;">Main Menu</small>
                    <ul class="nav flex-column gap-1">
                        @php
                            $navItems = [
                                ['dashboard', 'Dashboard', 'bi-speedometer2', 'dashboard'],
                                ['inventory.index', 'Inventory', 'bi-clipboard-data', 'inventory*'],
                                ['transactions.index', 'Transactions', 'bi-arrow-left-right', 'transactions*'],
                                ['orders.index', 'Orders', 'bi-cart3', 'orders*'],
                                ['products.index', 'Products', 'bi-box-seam', 'products*'],
                                ['brands.index', 'Brands', 'bi-award', 'brands*'],
                                ['categories.index', 'Categories', 'bi-tags', 'categories*'],
                            ];
                        @endphp
                        @foreach ($navItems as [$route, $label, $icon, $pattern])
                            <li class="nav-item">
                                <a class="nav-link rounded px-3 py-2 {{ request()->routeIs($pattern) ? 'active' : '' }}"
                                   href="{{ route($route) }}">
                                    <i class="bi {{ $icon }} me-2"></i> {{ $label }}
                                </a>
                            </li>
                        @endforeach

                        @if (Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link rounded px-3 py-2 {{ request()->routeIs('users*') ? 'active' : '' }}"
                                   href="{{ route('users.index') }}">
                                    <i class="bi bi-people me-2"></i> Users
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link rounded px-3 py-2 {{ request()->routeIs('adjustments*') ? 'active' : '' }}"
                                   href="{{ route('adjustments.create') }}">
                                    <i class="bi bi-sliders2 me-2"></i> Adjustments
                                </a>
                            </li>
                        @endif

                        @if (Auth::user()->isAdmin())
                            <li class="nav-item">
                                <a class="nav-link rounded px-3 py-2 {{ request()->routeIs('activity-logs*') ? 'active' : '' }}"
                                   href="{{ route('activity-logs.index') }}">
                                    <i class="bi bi-clock-history me-2"></i> Activity Log
                                </a>
                            </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link rounded px-3 py-2 {{ request()->routeIs('profile') ? 'active' : '' }}"
                               href="{{ route('profile') }}">
                                <i class="bi bi-person-circle me-2"></i> Profile
                            </a>
                        </li>
                    </ul>

                    @unless (Auth::user()->isAdmin())
                        <small class="text-muted px-3 mt-3 d-block" style="font-size: .65rem;">
                            <i class="bi bi-info-circle me-1"></i> Staff view — inventory operations
                        </small>
                    @endunless
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
                <div class="d-flex justify-content-between flex-wrap align-items-center pb-2 mb-4 border-bottom">
                    <h2 class="fw-bold text-dark mb-0">
                        @hasSection('heading')
                            @yield('heading')
                        @else
                            Welcome, <span class="text-warning fw-bold">{{ Auth::user()->name }}</span>!
                        @endif
                    </h2>
                    @hasSection('actions')
                        <div class="ms-auto pt-2 pt-sm-0">@yield('actions')</div>
                    @endif
                </div>

                @if (session('success'))
                    <div class="alert alert-success alert-dismissible fade show py-2 small fw-bold" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-dismissible fade show py-2 small fw-bold" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger py-2 small" role="alert">
                        <strong>Please correct the following:</strong>
                        <ul class="mb-0 mt-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
