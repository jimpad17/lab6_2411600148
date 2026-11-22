<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $order->receipt_number }} - JimTrack Fitness</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @page { size: A5; margin: 12mm; }
        body { font-family: "Segoe UI", Arial, sans-serif; font-size: 10pt; color: #212529; }
        .receipt-box { border: 2px solid #212529; padding: 18px; }
        .brand { color: #fd7e14; font-weight: 800; }
        .total-row { border-top: 2px double #212529; font-size: 12pt; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body class="bg-white">
    <div class="container py-4">
        <div class="no-print mb-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm" onclick="window.print()"><i class="bi bi-printer me-1"></i> Print receipt</button>
            <a href="{{ route('receipts.index') }}" class="btn btn-outline-secondary btn-sm">Back to receipts</a>
        </div>

        <div class="receipt-box mx-auto" style="max-width: 520px;">
            <div class="text-center mb-3">
                <h3 class="mb-0 fw-bold">JimTrack <span class="brand">Fitness</span></h3>
                <div class="small">Real-Time Inventory Tracking System</div>
                <hr class="my-2">
                <h5 class="fw-bold mb-0">{{ strtoupper($order->type) }} RECEIPT</h5>
                <code>{{ $order->receipt_number }}</code>
            </div>

            <table class="table table-sm table-borderless mb-2 small">
                <tr><th style="width:35%;">Order Number</th><td><code>{{ $order->order_number }}</code></td></tr>
                <tr><th>Order Date</th><td>{{ $order->order_date->format('M d, Y') }}</td></tr>
                <tr><th>Approved At</th><td>{{ $order->approved_at?->format('M d, Y h:i A') ?? '—' }}</td></tr>
                <tr><th>Encoded By</th><td>{{ $order->creator?->name ?? '—' }}</td></tr>
                <tr><th>Approved By</th><td>{{ $order->approver?->name ?? '—' }}</td></tr>
            </table>

            <table class="table table-sm table-bordered align-middle">
                <thead class="table-light">
                    <tr><th>Product</th><th class="text-center">Qty</th><th class="text-end">Unit Price</th><th class="text-end">Amount</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            <strong>{{ $order->product?->name ?? 'Deleted product' }}</strong><br>
                            <small class="text-muted">SKU: {{ $order->product?->sku ?? '—' }}</small>
                        </td>
                        <td class="text-center fw-bold">{{ $order->quantity }}</td>
                        <td class="text-end">₱{{ number_format($order->unit_price, 2) }}</td>
                        <td class="text-end">₱{{ number_format($order->total, 2) }}</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr class="total-row"><th colspan="3" class="text-end">TOTAL</th><th class="text-end">₱{{ number_format($order->total, 2) }}</th></tr>
                </tfoot>
            </table>

            <div class="small mt-4">
                <div class="mb-4">
                    <div class="border-bottom" style="width: 45%;">&nbsp;</div>
                    <span class="text-muted">Prepared by</span>
                </div>
                <div>
                    <div class="border-bottom" style="width: 45%;">&nbsp;</div>
                    <span class="text-muted">Approved by</span>
                </div>
            </div>

            <div class="text-center text-muted mt-4" style="font-size: .72rem;">
                This is an auto-generated receipt from JimTrack Fitness Inventory.
            </div>
        </div>
    </div>
</body>
</html>
