<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;

class DashboardController extends Controller
{
    /**
     * Real database statistics replacing the Lab 4 localStorage demo values.
     * Only active products are counted; inactive records remain in the DB.
     */
    public function index()
    {
        $totalProducts = Product::active()->count();
        $inStock = Product::active()->whereColumn('quantity', '>', 'reorder_level')->count();
        $lowStock = Product::lowStock()->count();
        $outOfStock = Product::outOfStock()->count();

        $inventoryValue = (float) Product::active()
            ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
            ->value('total');

        $categoryBreakdown = Product::active()
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->selectRaw('categories.name as category, COUNT(products.id) as items, SUM(products.quantity) as units, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('categories.id', 'categories.name')
            ->orderBy('categories.name')
            ->get();

        $brandBreakdown = Product::active()
            ->join('brands', 'brands.id', '=', 'products.brand_id')
            ->selectRaw('brands.name as brand, COUNT(products.id) as items, SUM(products.quantity * products.unit_price) as value')
            ->groupBy('brands.id', 'brands.name')
            ->orderByDesc('value')
            ->get();

        $recentProducts = Product::active()->with(['brand', 'category'])->latest()->take(5)->get();

        $topProducts = Product::active()->with('category')
            ->orderByRaw('(quantity * unit_price) DESC')
            ->take(5)
            ->get();

        $lowStockProducts = Product::active()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->orderBy('quantity')
            ->take(5)
            ->get();

        $recentTransactions = InventoryTransaction::with(['product', 'user'])
            ->latest()
            ->take(6)
            ->get();

        $recentActivity = \App\Models\ActivityLog::with('user')->latest()->take(5)->get();

        $activeUsers = User::active()->count();
        $activeBrands = Brand::active()->count();
        $activeCategories = Category::active()->count();

        $hour = (int) now()->format('H');
        $greeting = match (true) {
            $hour >= 5 && $hour < 12 => 'Good Morning',
            $hour >= 12 && $hour < 17 => 'Good Afternoon',
            $hour >= 17 && $hour < 21 => 'Good Evening',
            default => 'Good Night',
        };

        return view('dashboard', compact(
            'totalProducts',
            'inStock',
            'lowStock',
            'outOfStock',
            'inventoryValue',
            'categoryBreakdown',
            'brandBreakdown',
            'recentProducts',
            'topProducts',
            'lowStockProducts',
            'recentTransactions',
            'recentActivity',
            'activeUsers',
            'activeBrands',
            'activeCategories',
            'greeting'
        ));
    }
}
