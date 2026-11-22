<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

/** Receipts are generated automatically from approved purchase/sales orders. */
class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::approved()->whereNotNull('receipt_number')
            ->with(['product', 'creator']);

        if (! auth()->user()->isAdmin()) {
            $query->forUser(auth()->id());
        }

        $receipts = $query->when($request->filled('type'), fn ($q) => $q->where('type', $request->input('type')))
            ->orderByDesc('approved_at')
            ->paginate(15)
            ->withQueryString();

        return view('receipts.index', [
            'receipts' => $receipts,
            'filters' => $request->only(['type']),
            'totalReceipts' => (clone $query)->count(),
        ]);
    }

    public function show(Order $order)
    {
        abort_unless(auth()->user()->isAdmin() || (int) $order->user_id === auth()->id(), 403);
        abort_unless($order->status === Order::STATUS_APPROVED && $order->receipt_number, 404);

        $order->load(['product.brand', 'product.category', 'creator', 'approver']);

        return view('receipts.show', ['order' => $order]);
    }
}
