<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderApprovedNotification;
use App\Rules\ValidOrderDate;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /** Staff see only their own orders; admins see the whole queue. */
    public function index(Request $request)
    {
        return $this->ordersView($request, $request->input('type'));
    }

    /** Dedicated menu page for sales orders. */
    public function sales(Request $request)
    {
        return $this->ordersView($request, Order::TYPE_SALES, 'Sales Order');
    }

    /** Dedicated menu page for purchase orders. */
    public function purchase(Request $request)
    {
        return $this->ordersView($request, Order::TYPE_PURCHASE, 'Purchase Order');
    }

    private function ordersView(Request $request, ?string $type, ?string $heading = null)
    {
        $query = Order::with(['product', 'creator']);

        if (! auth()->user()->isAdmin()) {
            $query->forUser(auth()->id());
        }

        $query->when($type, fn ($q) => $q->where('type', $type))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->betweenDates($request->input('from'), $request->input('to'))
            ->orderByDesc('order_date')
            ->orderByDesc('id');

        $orders = $query->paginate(15)->withQueryString();

        $base = Order::query();
        if (! auth()->user()->isAdmin()) {
            $base->forUser(auth()->id());
        }

        $filters = $request->only(['type', 'status', 'from', 'to']);
        if ($type) {
            $filters['type'] = $type;
        }

        return view('orders.index', [
            'orders' => $orders,
            'pageHeading' => $heading,
            'pendingCount' => (clone $base)->pending()->count(),
            'approvedCount' => (clone $base)->approved()->count(),
            'salesTotal' => (float) (clone $base)->approved()->sales()->sum('quantity'),
            'purchaseTotal' => (float) (clone $base)->approved()->purchases()->sum('quantity'),
            'types' => Order::typeOptions(),
            'statuses' => [
                Order::STATUS_PENDING => 'Pending',
                Order::STATUS_APPROVED => 'Approved',
                Order::STATUS_REJECTED => 'Rejected',
            ],
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return view('orders.create', [
            'products' => Product::active()->with(['brand', 'category'])->orderBy('name')->get(),
            'types' => Order::typeOptions(),
            'defaultDate' => today()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateOrder($request);

        if ($data['type'] === Order::TYPE_SALES) {
            $product = Product::findOrFail($data['product_id']);

            if ((int) $data['quantity'] > (int) $product->quantity) {
                return back()->withInput()->withErrors([
                    'quantity' => "Not enough stock. Only {$product->quantity} pcs of '{$product->name}' are on hand.",
                ]);
            }
        }

        $order = Order::create(array_merge($data, [
            'order_number' => Order::generateNumber($data['type']),
            'status' => Order::STATUS_PENDING,
            'user_id' => auth()->id(),
        ]));

        ActivityLogger::log('created', 'Orders',
            "Submitted {$order->type_label} {$order->order_number} for {$order->quantity} pcs — awaiting admin approval.");

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order submitted. An admin must review and approve it before stock is updated.');
    }

    public function show(Order $order)
    {
        $this->ensureVisible($order);
        $order->load(['product.brand', 'product.category', 'creator', 'approver', 'editor']);

        return view('orders.show', ['order' => $order]);
    }

    public function edit(Order $order)
    {
        $this->ensureAdmin();
        $this->ensurePending($order);

        return view('orders.edit', [
            'order' => $order,
            'products' => Product::with(['brand', 'category'])->orderBy('name')->get(),
            'types' => Order::typeOptions(),
        ]);
    }

    /** Admin corrects a staff mistake BEFORE the order is posted to stock. */
    public function update(Request $request, Order $order)
    {
        $this->ensureAdmin();
        $this->ensurePending($order);

        $data = $this->validateOrder($request);
        $originalQuantity = (int) $order->quantity;

        if ($data['type'] === Order::TYPE_SALES) {
            $product = Product::findOrFail($data['product_id']);

            if ((int) $data['quantity'] > (int) $product->quantity) {
                return back()->withInput()->withErrors([
                    'quantity' => "Not enough stock. Only {$product->quantity} pcs of '{$product->name}' are on hand.",
                ]);
            }
        }

        $changes = $data;
        if ($originalQuantity !== (int) $data['quantity']) {
            $changes['original_quantity'] = $originalQuantity;
        }
        $changes['edited_by'] = auth()->id();

        $order->update($changes);

        ActivityLogger::log('updated', 'Orders',
            "Admin corrected {$order->type_label} {$order->order_number}: {$originalQuantity} → {$order->quantity} pcs.");

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order corrected. Review it again, then approve when ready.');
    }

    /** Approval is the only place an order touches inventory. */
    public function approve(Order $order)
    {
        $this->ensureAdmin();
        $this->ensurePending($order);

        $product = Product::whereKey($order->product_id)->lockForUpdate()->firstOrFail();

        if (! $product->is_active) {
            return back()->with('error', "'{$product->name}' is unavailable. Restore it before approving this order.");
        }

        if ($order->type === Order::TYPE_SALES && (int) $order->quantity > (int) $product->quantity) {
            return back()->with('error', "Not enough stock. Only {$product->quantity} pcs are on hand.");
        }

        try {
            DB::transaction(function () use ($order): void {
                $isPurchase = $order->type === Order::TYPE_PURCHASE;

                InventoryTransaction::create([
                    'product_id' => $order->product_id,
                    'type' => $isPurchase ? InventoryTransaction::TYPE_IN : InventoryTransaction::TYPE_OUT,
                    'direction' => $isPurchase ? InventoryTransaction::DIRECTION_IN : InventoryTransaction::DIRECTION_OUT,
                    'quantity' => $order->quantity,
                    'reference_number' => $order->order_number,
                    'notes' => ($order->notes ? $order->notes.' — ' : '')
                        .'Approved '.strtolower($order->type_label).' submitted by '.($order->creator?->name ?? 'staff').'.',
                    'user_id' => auth()->id(),
                ]);

                $order->update([
                    'status' => Order::STATUS_APPROVED,
                    'receipt_number' => Order::generateReceiptNumber(),
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        ActivityLogger::log('updated', 'Orders',
            "Approved {$order->type_label} {$order->order_number}; stock was updated and the audit trail recorded.");

        // Bell + email notification with a direct link to the printable receipt.
        $approved = $order->fresh()->load('product');
        $recipients = User::query()
            ->where(function ($query) use ($approved) {
                $query->whereKey($approved->user_id)
                    ->orWhere('role', 'admin');
            })
            ->where('is_active', true)
            ->get()
            ->unique('id')
            ->values();

        if ($recipients->isNotEmpty()) {
            try {
                Notification::send($recipients, new OrderApprovedNotification($approved));
            } catch (\Throwable $exception) {
                // The stock movement and receipt are already committed — never fail the
                // approval because a mail transport is unavailable.
                report($exception);
            }
        }

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order approved. Stock, receipt and notifications were updated.');
    }

    public function reject(Order $order)
    {
        $this->ensureAdmin();
        $this->ensurePending($order);

        $order->update(['status' => Order::STATUS_REJECTED]);

        ActivityLogger::log('updated', 'Orders',
            "Rejected {$order->type_label} {$order->order_number} — no stock changes were made.");

        return redirect()->route('orders.show', $order)
            ->with('success', 'Order rejected. No stock was moved.');
    }

    private function validateOrder(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(array_keys(Order::typeOptions()))],
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'order_date' => ['required', 'date', new ValidOrderDate()],
            'reference_number' => ['nullable', 'string', 'max:150'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Please choose a product.',
            'quantity.min' => 'Quantity must be at least 1.',
            'order_date.required' => 'Please choose an order date.',
        ]);
    }

    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403, 'Only admins can review orders.');
    }

    private function ensureVisible(Order $order): void
    {
        abort_unless(auth()->user()->isAdmin() || (int) $order->user_id === auth()->id(), 403);
    }

    private function ensurePending(Order $order): void
    {
        abort_unless($order->status === Order::STATUS_PENDING, 409, 'Only pending orders can be changed.');
    }
}
