<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Stock-level overview with quick Stock In / Stock Out actions.
     */
    public function index(Request $request)
    {
        $products = Product::applyFilters($request->all())
            ->with(['brand', 'category'])
            ->paginate(10)
            ->withQueryString();

        return view('inventory.index', [
            'products' => $products,
            'brands' => \App\Models\Brand::orderBy('name')->get(),
            'categories' => \App\Models\Category::orderBy('name')->get(),
            'totalActive' => Product::active()->count(),
            'unitsOnHand' => (int) Product::active()->sum('quantity'),
            'stockValue' => (float) Product::active()->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')->value('total'),
            'lowCount' => Product::lowStock()->count(),
            'outCount' => Product::outOfStock()->count(),
            'recentTransactions' => InventoryTransaction::with(['product', 'user'])->latest()->take(8)->get(),
            'filters' => $request->only(['brand_id', 'category_id', 'status', 'search']),
        ]);
    }

    public function stock(Request $request, Product $product)
    {
        $data = $request->validate([
            'type' => ['required', 'in:in,out'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
        ], [
            'type.required' => 'Choose Stock In or Stock Out.',
            'quantity.required' => 'Enter the number of units.',
            'quantity.min' => 'Quantity must be at least 1.',
        ]);

        $quantity = (int) $data['quantity'];

        if ($data['type'] === 'out' && $quantity > (int) $product->quantity) {
            return back()->with('error', "Not enough stock. Only {$product->quantity} pcs of '{$product->name}' are on hand.");
        }

        try {
            // Lab 6: the observer applies the stock change, not this controller.
            DB::transaction(function () use ($product, $data, $quantity): void {
                InventoryTransaction::create([
                    'product_id' => $product->id,
                    'type' => $data['type'],
                    'direction' => $data['type'],
                    'quantity' => $quantity,
                    'reference_number' => 'QUICK-'.strtoupper($data['type']),
                    'notes' => 'Recorded from the Stock Overview quick action.',
                    'user_id' => auth()->id(),
                ]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        $label = $data['type'] === 'in' ? 'Stock In' : 'Stock Out';
        ActivityLogger::log('updated', 'Inventory', "{$label} of {$quantity} pcs for '{$product->name}'.");

        return back()->with('success', "{$label} of {$quantity} pcs recorded for '{$product->name}'.");
    }
}
