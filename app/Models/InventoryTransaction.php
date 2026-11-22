<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryTransaction extends Model
{
    use HasFactory;

    public const TYPE_IN = 'in';
    public const TYPE_OUT = 'out';
    public const TYPE_ADJUSTMENT = 'adjustment';

    public const DIRECTION_IN = 'in';
    public const DIRECTION_OUT = 'out';

    /**
     * Every stock movement keeps its reference number, notes, actor and the
     * balance that remained after the movement was applied.
     */
    protected $fillable = [
        'product_id',
        'type',
        'direction',
        'quantity',
        'balance_after',
        'reference',
        'reference_number',
        'notes',
        'user_id',
        'is_reversed',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'balance_after' => 'integer',
        'is_reversed' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Lab 5 called this column `reference`; Lab 6 calls it `reference_number`.
     * The migration renames the column, these two accessors keep old code working.
     */
    public function getReferenceAttribute(?string $value): ?string
    {
        return $value ?? $this->reference_number;
    }

    public function setReferenceAttribute(?string $value): void
    {
        $this->attributes['reference_number'] = $value;
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => $this->direction === self::DIRECTION_OUT
                ? 'Adjustment (−)'
                : 'Adjustment (+)',
            default => ucfirst((string) $this->type),
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_IN => 'bg-success',
            self::TYPE_OUT => 'bg-warning text-dark',
            self::TYPE_ADJUSTMENT => 'bg-info text-dark',
            default => 'bg-secondary',
        };
    }

    /** Signed effect on the product quantity: + for inbound, − for outbound. */
    public function signedQuantity(): int
    {
        $quantity = (int) $this->quantity;

        return match ($this->type) {
            self::TYPE_IN => $quantity,
            self::TYPE_OUT => -$quantity,
            self::TYPE_ADJUSTMENT => $this->direction === self::DIRECTION_OUT ? -$quantity : $quantity,
            default => $quantity,
        };
    }

    public function getQuantityDisplayAttribute(): string
    {
        $sign = $this->signedQuantity() < 0 ? '−' : '+';

        return $sign.abs($this->signedQuantity());
    }

    public function scopeStockIn(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_IN);
    }

    public function scopeStockOut(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_OUT);
    }

    public function scopeAdjustments(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ADJUSTMENT);
    }

    public function scopeNotReversed(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('is_reversed', false)->orWhereNull('is_reversed');
        });
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }

    public function scopeForProduct(Builder $query, int $productId): Builder
    {
        return $query->where('product_id', $productId);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        return $query;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_IN => 'Stock In',
            self::TYPE_OUT => 'Stock Out',
            self::TYPE_ADJUSTMENT => 'Adjustment',
        ];
    }
}
