<?php

namespace App\Http\Controllers;

use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $categories = Product::active()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, COUNT(products.id) as items, SUM(products.quantity) as units, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        $brands = Product::active()
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->selectRaw('brands.name as brand, COUNT(products.id) as items, SUM(products.quantity) as units, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('brands.id', 'brands.name')
            ->orderBy('brands.name')
            ->get();

        $totalValue = (float) Product::active()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
            ->value('total');
        $totalItems = (int) Product::active()->count();

        $lowStockProducts = Product::active()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')->get();

        $inactiveProducts = Product::inactive()->with(['brand', 'category'])->orderBy('name')->get();

        $transactionCounts = InventoryTransaction::selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $recentTransactions = InventoryTransaction::with(['product', 'user'])
            ->latest()->take(10)->get();

        return view('reports.index', compact(
            'categories',
            'brands',
            'totalValue',
            'totalItems',
            'lowStockProducts',
            'inactiveProducts',
            'transactionCounts',
            'recentTransactions'
        ));
    }

    public function exportCsv()
    {
        $categories = Product::active()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, COUNT(products.id) as items, SUM(products.quantity) as units, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        $filename = 'jimtrack-inventory-report-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($categories) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Category', 'Products', 'Units On Hand', 'Inventory Value']);

            foreach ($categories as $row) {
                fputcsv($handle, [
                    $row->category,
                    $row->items,
                    $row->units,
                    number_format((float) $row->value, 2, '.', ''),
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    /**
     * Lab 6 - Stock Movement, Low Stock and Inventory Valuation reports.
     */
    public function tracking(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $movements = InventoryTransaction::with(['product', 'user'])
            ->betweenDates($filters['from'] ?? null, $filters['to'] ?? null)
            ->recent()
            ->get();

        $lowStockProducts = Product::active()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->with(['brand', 'category'])
            ->orderBy('quantity')
            ->get();

        $valuation = Product::active()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, COUNT(products.id) as items, SUM(products.quantity) as units, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        return view('reports.tracking', [
            'movements' => $movements,
            'lowStockProducts' => $lowStockProducts,
            'valuation' => $valuation,
            'filters' => $filters,
            'totalValue' => (float) $valuation->sum('value'),
            'totalUnits' => (int) $valuation->sum('units'),
            'totalIn' => (int) $movements->where('type', InventoryTransaction::TYPE_IN)->sum('quantity'),
            'totalOut' => (int) $movements->where('type', InventoryTransaction::TYPE_OUT)->sum('quantity'),
        ]);
    }

    public function exportTrackingCsv(Request $request)
    {
        $filters = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $movements = InventoryTransaction::with(['product', 'user'])
            ->betweenDates($filters['from'] ?? null, $filters['to'] ?? null)
            ->recent()
            ->get();

        $filename = 'jimtrack-stock-movements-'.now()->format('Y-m-d').'.csv';

        return response()->stream(function () use ($movements) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Date', 'Type', 'Product', 'SKU', 'Quantity', 'Direction', 'Balance After', 'Reference', 'User', 'Notes']);

            foreach ($movements as $movement) {
                fputcsv($handle, [
                    $movement->created_at?->format('Y-m-d H:i:s'),
                    $movement->type_label,
                    $movement->product?->name ?? 'Deleted product',
                    $movement->product?->sku ?? '',
                    $movement->quantity,
                    $movement->signedQuantity(),
                    $movement->balance_after ?? '',
                    $movement->reference_number ?? '',
                    $movement->user?->name ?? 'System',
                    $movement->notes ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
