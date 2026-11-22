<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;

class ProfileController extends Controller
{
    /** Staff profile page: account details plus a compact product report. */
    public function index()
    {
        $user = auth()->user();

        return view('profile.index', [
            'user' => $user,
            'recentOrders' => Order::forUser($user->id)->with('product')->latest()->limit(5)->get(),
            'productCount' => Product::active()->count(),
            'lowStockCount' => Product::active()->lowStock()->count(),
        ]);
    }
}
