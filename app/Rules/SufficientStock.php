<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * "After" hook implemented as a custom Rule: an outbound movement can never
 * take the product below zero. The observer repeats the check at write time,
 * so concurrent requests are still protected.
 */
class SufficientStock implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $productId = request()->input('product_id');
        $type = request()->input('type');
        $direction = request()->input('direction');

        if (empty($productId) || ! is_numeric($value) || (int) $value <= 0) {
            return;
        }

        $isOutbound = $type === 'out'
            || ($type === 'adjustment' && $direction === 'out');

        if (! $isOutbound) {
            return;
        }

        $product = Product::find($productId);

        if (! $product) {
            return; // The `exists` rule reports the missing product.
        }

        if ((int) $value > (int) $product->quantity) {
            $fail("Not enough stock. Only {$product->quantity} pcs of '{$product->name}' are on hand.");
        }
    }
}
