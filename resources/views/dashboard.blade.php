@extends('layouts.app')

@section('title', 'Dashboard')

@section('heading')
    {{ $greeting }}, <span class="text-warning fw-bold">{{ Auth::user()->name }}</span>!
@endsection

@section('actions')
    <a href="{{ route('transactions.create') }}" class="btn btn-success btn-sm px-3 me-1">
        <i class="bi bi-plus-circle me-1"></i> Record Movement
    </a>
    @if (Auth::user()->isAdmin())
        <a href="{{ route('reports.tracking') }}" class="btn btn-outline-primary btn-sm px-3 me-1">
            <i class="bi bi-activity me-1"></i> Tracking
        </a>
        <a href="{{ route('reports.index') }}" class="btn btn-outline-warning btn-sm px-3">
            <i class="bi bi-bar-chart-line me-1"></i> Reports
        </a>
    @endif
@endsection

@section('content')
    <!-- KPI cards: real MySQL values (replaces Lab 4 localStorage values) -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Total Products</h6>
                    <h2 class="fw-bold text-primary mb-0" id="live-total-products">{{ $totalProducts }}</h2>
                    <small class="text-muted">{{ $inStock }} items in stock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Low Stock Alerts</h6>
                    <h2 class="fw-bold text-warning mb-0" id="live-low-stock">{{ $lowStock }}</h2>
                    <small class="text-muted">At or below reorder level</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Out of Stock</h6>
                    <h2 class="fw-bold text-danger mb-0" id="live-out-of-stock">{{ $outOfStock }}</h2>
                    <small class="text-muted">Needs immediate restock</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card stat-card shadow-sm text-center py-3">
                <div class="card-body py-2">
                    <h6 class="card-title text-uppercase fw-bold mb-2">Inventory Value</h6>
                    <h2 class="fw-bold text-success mb-0" id="live-inventory-value">₱{{ number_format($inventoryValue, 2) }}</h2>
                    <small class="text-muted">{{ $activeBrands }} brands · {{ $activeCategories }} categories</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab 6: live polling status -->
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <small class="text-muted">
            <i class="bi bi-broadcast-pin text-success live-dot me-1"></i>
            Live inventory tracking — refreshes automatically every 30 seconds
        </small>
        <small class="text-muted">Last updated: <strong id="last-updated" class="text-dark">{{ now()->format('M d, Y h:i A') }}</strong></small>
    </div>

    <!-- Visualizations: same Chart.js approach as Lab 4, now driven by Eloquent data -->
    <div class="row mb-4">
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-pie-chart-fill text-primary me-2"></i>Inventory Category Spread</h6>
                <div class="chart-box"><canvas id="categoryChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Stock Health</h6>
                <div class="chart-box"><canvas id="stockHealthChart"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="card h-100 shadow-sm p-3">
                <h6 class="fw-bold mb-3"><i class="bi bi-trophy-fill text-warning me-2"></i>Top 5 Products by Value</h6>
                <div class="chart-box"><canvas id="topProductsChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center border-bottom-0 pt-3">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history text-primary me-2"></i>Recently Added Products</h5>
                    <a href="{{ route('products.index') }}" class="small text-decoration-none fw-semibold">View all</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">SKU</th><th>Product</th><th>Brand</th><th>Category</th><th>Qty</th><th>Status</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($recentProducts as $item)
                                    <tr>
                                        <td class="ps-3"><code class="sku-tag">{{ $item->sku }}</code></td>
                                        <td><strong>{{ $item->name }}</strong></td>
                                        <td><small>{{ $item->brand?->name ?? '—' }}</small></td>
                                        <td><span class="badge badge-category">{{ $item->category?->name ?? '—' }}</span></td>
                                        <td>{{ $item->quantity }}</td>
                                        <td>
                                            <span class="badge badge-{{ $item->quantity == 0 ? 'out' : ($item->isLowStock() ? 'low' : 'in') }}">
                                                {{ $item->stock_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No products yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-bell-fill text-warning me-2"></i>Restock Alerts</h5>
                </div>
                <div class="card-body" id="live-low-stock-list">
                    @forelse ($lowStockProducts as $item)
                        <div class="d-flex justify-content-between align-items-center border-bottom py-2 small">
                            <div>
                                <div class="fw-bold">{{ $item->name }}</div>
                                <div class="text-muted">{{ $item->quantity }} pcs left · min {{ $item->reorder_level }}</div>
                            </div>
                            <span class="badge badge-{{ $item->quantity == 0 ? 'out' : 'low' }}">{{ $item->stock_status }}</span>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">All products are above reorder level. Nice!</p>
                    @endforelse
                    <a href="{{ route('inventory.index') }}" class="small text-decoration-none fw-semibold d-block mt-3">
                        Open stock overview →
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right text-success me-2"></i>Recent Stock Movements</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr><th class="ps-3">Product</th><th>Type</th><th>Quantity</th><th>Reference</th><th>User</th><th>Date</th></tr>
                            </thead>
                            <tbody id="live-recent-transactions">
                                @forelse ($recentTransactions as $transaction)
                                    <tr>
                                        <td class="ps-3">{{ $transaction->product?->name ?? 'Deleted product' }}</td>
                                        <td><span class="badge {{ $transaction->badge_class }}">{{ $transaction->type_label }}</span></td>
                                        <td class="fw-bold {{ $transaction->signedQuantity() < 0 ? 'text-danger' : 'text-success' }}">{{ $transaction->quantity_display }}</td>
                                        <td><code>{{ $transaction->reference_number ?? '—' }}</code></td>
                                        <td>{{ $transaction->user?->name ?? 'System' }}</td>
                                        <td>{{ $transaction->created_at->format('M d, Y h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center py-4 text-muted">No stock movements recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white pt-3 border-bottom-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold"><i class="bi bi-activity text-primary me-2"></i>Recent Activity</h5>
                    @if (Auth::user()->isAdmin())
                        <a href="{{ route('activity-logs.index') }}" class="small text-decoration-none fw-semibold">View all</a>
                    @endif
                </div>
                <div class="card-body">
                    @forelse ($recentActivity as $log)
                        <div class="border-bottom py-2 small">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold">{{ $log->user?->name ?? 'System' }}</span>
                                <span class="badge bg-light text-dark">{{ $log->module }}</span>
                            </div>
                            <div class="text-muted">{{ $log->description }}</div>
                            <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                        </div>
                    @empty
                        <p class="text-muted small mb-0">No activity recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    <script>
        const categoryLabels = @json($categoryBreakdown->pluck('category'));
        const categoryItems = @json($categoryBreakdown->pluck('items'));
        const topNames = @json($topProducts->pluck('name'));
        const topValues = @json($topProducts->pluck('stock_value'));

        new Chart(document.getElementById('categoryChart'), {
            type: 'doughnut',
            data: {
                labels: categoryLabels,
                datasets: [{ data: categoryItems, backgroundColor: ['#fd7e14', '#0d6efd', '#198754', '#6f42c1', '#ffc107', '#20c997'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('stockHealthChart'), {
            type: 'pie',
            data: {
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                datasets: [{ data: [{{ $inStock }}, {{ $lowStock }}, {{ $outOfStock }}], backgroundColor: ['#198754', '#ffc107', '#dc3545'] }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
        });

        new Chart(document.getElementById('topProductsChart'), {
            type: 'bar',
            data: {
                labels: topNames,
                datasets: [{ label: 'Stock Value (₱)', data: topValues, backgroundColor: '#212529' }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
    </script>

    <!-- Lab 6: AJAX polling (30s) for stats, low stock and recent movements -->
    <script src="{{ asset('js/inventory-live.js') }}" defer></script>
@endpush
