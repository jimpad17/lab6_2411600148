<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class StockAdjustmentController extends Controller
{
    public const REASONS = [
        'physical_count' => 'Physical count discrepancy',
        'damaged' => 'Damaged goods',
        'theft_loss' => 'Theft / loss',
        'system_error' => 'System error',
        'other' => 'Other',
    ];

    /** Physical-count adjustment form (authorized users only). */
    public function create()
    {
        return view('adjustments.create', [
            'products' => Product::active()->with('brand')->orderBy('name')->get(),
            'reasons' => self::REASONS,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('products', 'id')],
            'physical_count' => ['required', 'integer', 'min:0', 'max:1000000'],
            'reason' => ['required', Rule::in(array_keys(self::REASONS))],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'product_id.required' => 'Please choose a product to count.',
            'physical_count.required' => 'Enter the physical count you recorded.',
            'reason.required' => 'Choose a reason for the adjustment.',
        ]);

        $product = Product::findOrFail($data['product_id']);
        $difference = (int) $data['physical_count'] - (int) $product->quantity;

        if ($difference === 0) {
            return back()->withInput()->with('error', 'The physical count matches the system quantity — no adjustment needed.');
        }

        try {
            $transaction = DB::transaction(function () use ($product, $difference, $data) {
                return InventoryTransaction::create([
                    'product_id' => $product->id,
                    'type' => InventoryTransaction::TYPE_ADJUSTMENT,
                    'direction' => $difference > 0
                        ? InventoryTransaction::DIRECTION_IN
                        : InventoryTransaction::DIRECTION_OUT,
                    'quantity' => abs($difference),
                    'reference_number' => 'ADJ-'.strtoupper($data['reason']),
                    'notes' => trim((self::REASONS[$data['reason']].'. '.($data['notes'] ?? ''))),
                    'user_id' => auth()->id(),
                ]);
            });
        } catch (InsufficientStockException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        ActivityLogger::log('updated', 'Inventory',
            "Adjusted '{$product->name}' by {$difference} pcs (".self::REASONS[$data['reason']].").");

        return redirect()->route('transactions.show', $transaction)
            ->with('success', 'Physical count recorded. The stock level and audit trail were updated.');
    }
}
