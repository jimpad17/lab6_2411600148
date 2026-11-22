<?php

namespace App\Rules;

use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Sales/purchase orders may be dated today or in the future, never in the past.
 */
class ValidOrderDate implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        try {
            $date = Carbon::parse($value)->startOfDay();
        } catch (\Throwable $exception) {
            return; // The `date` rule reports malformed values.
        }

        if ($date->lt(Carbon::today()->startOfDay())) {
            $fail('The order date cannot be in the past. Choose today or a future date.');
        }
    }
}
