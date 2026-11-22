<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InventoryStatResource;
use App\Http\Resources\InventoryTransactionResource;
use App\Http\Resources\ProductStockResource;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class InventoryApiController extends Controller
{
    /** GET /api/dashboard/stats - KPI payload for AJAX polling. */
    public function stats(): JsonResponse
    {
        $stats = [
            'total_products' => Product::active()->count(),
            'in_stock' => Product::active()->whereColumn('quantity', '>', 'reorder_level')->count(),
            'low_stock' => Product::active()->whereColumn('quantity', '<=', 'reorder_level')->count(),
            'out_of_stock' => Product::active()->where('quantity', 0)->count(),
            'inventory_value' => (float) Product::active()
                ->selectRaw('COALESCE(SUM(quantity * unit_price), 0) as total')
                ->value('total'),
            'total_movements' => InventoryTransaction::count(),
            'movements_today' => InventoryTransaction::whereDate('created_at', today())->count(),
            'generated_at' => now(),
        ];

        return (new InventoryStatResource($stats))->response();
    }

    /** GET /api/products/low-stock */
    public function lowStock(): JsonResponse
    {
        $products = Product::active()
            ->whereColumn('quantity', '<=', 'reorder_level')
            ->with(['brand', 'category'])
            ->orderBy('quantity')
            ->limit(10)
            ->get();

        return ProductStockResource::collection($products)->response();
    }

    /** GET /api/transactions/recent */
    public function recentTransactions(): JsonResponse
    {
        $transactions = InventoryTransaction::with(['product', 'user'])
            ->notReversed()
            ->recent()
            ->limit(8)
            ->get();

        return InventoryTransactionResource::collection($transactions)->response();
    }

    /** GET /api/products/{product}/stock - used by the create/adjustment forms. */
    public function productStock(Product $product): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'quantity' => (int) $product->quantity,
                'reorder_level' => (int) $product->reorder_level,
                'status' => $product->stock_status,
            ],
        ]);
    }
}
