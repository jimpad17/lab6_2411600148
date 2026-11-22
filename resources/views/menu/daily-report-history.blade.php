@extends('layouts.app')

@section('title', 'Daily Report History')

@section('heading')
    Daily Report <span class="text-warning fw-bold">History</span>
@endsection

@section('content')
    <div class="card mb-4 shadow-sm bg-white p-2">
        <div class="card-body">
            <form method="GET" action="{{ route('daily-report-history') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i> Filter</button>
                    <a href="{{ route('daily-report-history') }}" class="btn btn-outline-secondary btn-sm">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white pt-3 border-bottom-0">
            <h5 class="mb-0 fw-bold"><i class="bi bi-calendar2-week text-warning me-2"></i> Daily Totals (approved orders)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 small">
                    <thead class="table-light"><tr><th class="ps-4">Date</th><th>Type</th><th>Orders</th><th>Units</th><th class="text-end pe-4">Action</th></tr></thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td class="ps-4 fw-bold">{{ \Illuminate\Support\Carbon::parse($row->order_date)->format('M d, Y') }}</td>
                                <td><span class="badge text-bg-{{ $row->type === 'sales' ? 'success' : 'warning' }}">{{ strtoupper($row->type) }}</span></td>
                                <td>{{ $row->orders }}</td>
                                <td class="fw-bold">{{ number_format($row->units) }}</td>
                                <td class="text-end pe-4">
                                    <a href="{{ route('daily-report', ['date' => $row->order_date]) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i> View report
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center py-4 text-muted">No approved orders recorded yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-body border-top">{{ $rows->links() }}</div>
    </div>
@endsection
