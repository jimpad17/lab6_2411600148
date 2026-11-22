<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;

/**
 * Dedicated pages for the menu groups shown in the approved app design:
 * Product Report, Daily Report, Daily Report History and Product History.
 */
class MenuController extends Controller
{
    public function productReport(Request $request)
    {
        $query = Product::with(['brand', 'category'])
            ->withCount([
                'orders as sales_orders_count' => fn ($q) => $q->sales()->approved(),
                'orders as purchase_orders_count' => fn ($q) => $q->purchases()->approved(),
            ]);

        if ($request->filled('search')) {
            $term = '%'.strip_tags((string) $request->input('search')).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('menu.product-report', [
            'products' => $products,
            'brands' => \App\Models\Brand::orderBy('name')->get(),
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'filters' => $request->only(['search', 'brand_id', 'category_id']),
            'totalProducts' => Product::count(),
            'totalSales' => (int) Order::approved()->sales()->sum('quantity'),
            'totalPurchases' => (int) Order::approved()->purchases()->sum('quantity'),
        ]);
    }

    public function dailyReport(Request $request)
    {
        $date = $request->input('date', today()->toDateString());

        $sales = Order::approved()->sales()->whereDate('order_date', $date)
            ->with(['product', 'creator'])->orderByDesc('id')->get();
        $purchases = Order::approved()->purchases()->whereDate('order_date', $date)
            ->with(['product', 'creator'])->orderByDesc('id')->get();
        $movements = InventoryTransaction::with(['product', 'user'])
            ->whereDate('created_at', $date)->latest()->get();

        return view('menu.daily-report', [
            'date' => $date,
            'sales' => $sales,
            'purchases' => $purchases,
            'movements' => $movements,
            'salesUnits' => (int) $sales->sum('quantity'),
            'purchaseUnits' => (int) $purchases->sum('quantity'),
            'salesValue' => (float) $sales->sum(fn ($o) => $o->total),
            'purchaseValue' => (float) $purchases->sum(fn ($o) => $o->total),
        ]);
    }

    public function dailyReportHistory(Request $request)
    {
        $rows = Order::approved()
            ->selectRaw('order_date, type, COUNT(*) as orders, SUM(quantity) as units')
            ->when($request->filled('from'), fn ($q) => $q->whereDate('order_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('order_date', '<=', $request->input('to')))
            ->groupBy('order_date', 'type')
            ->orderByDesc('order_date')
            ->paginate(15)
            ->withQueryString();

        return view('menu.daily-report-history', [
            'rows' => $rows,
            'filters' => $request->only(['from', 'to']),
        ]);
    }

    public function productHistory(Request $request)
    {
        $query = Product::with(['brand', 'category'])
            ->withCount([
                'transactions',
                'transactions as stock_in_count' => fn ($q) => $q->where('type', 'in'),
                'transactions as stock_out_count' => fn ($q) => $q->where('type', 'out'),
            ]);

        if ($request->filled('search')) {
            $term = '%'.strip_tags((string) $request->input('search')).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)->orWhere('sku', 'like', $term);
            });
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();
        $recent = InventoryTransaction::with(['product', 'user'])->latest()->take(10)->get();

        return view('menu.product-history', [
            'products' => $products,
            'recent' => $recent,
            'filters' => $request->only(['search']),
            'movementCount' => InventoryTransaction::count(),
        ]);
    }
}
