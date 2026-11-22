<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    public const TYPE_PURCHASE = 'purchase';
    public const TYPE_SALES = 'sales';

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'order_number',
        'receipt_number',
        'type',
        'product_id',
        'quantity',
        'original_quantity',
        'order_date',
        'status',
        'reference_number',
        'notes',
        'edit_notes',
        'user_id',
        'approved_by',
        'edited_by',
        'approved_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'original_quantity' => 'integer',
        'order_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Staff member who submitted the order. */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_REJECTED);
    }

    public function scopeSales(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_SALES);
    }

    public function scopePurchases(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_PURCHASE);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): Builder
    {
        return $query
            ->when($from, fn (Builder $q) => $q->whereDate('order_date', '>=', $from))
            ->when($to, fn (Builder $q) => $q->whereDate('order_date', '<=', $to));
    }

    public function getTypeLabelAttribute(): string
    {
        return $this->type === self::TYPE_PURCHASE ? 'Purchase Order' : 'Sales Order';
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst((string) $this->status);
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_APPROVED => 'badge-in',
            self::STATUS_REJECTED => 'badge-out',
            default => 'badge-low',
        };
    }

    /** Current unit price of the ordered product, used on the printable receipt. */
    public function getUnitPriceAttribute(): float
    {
        return round((float) ($this->product?->unit_price ?? 0), 2);
    }

    public function getTotalAttribute(): float
    {
        return round($this->unit_price * (int) $this->quantity, 2);
    }

    public function getWasEditedAttribute(): bool
    {
        return $this->original_quantity !== null && (int) $this->original_quantity !== (int) $this->quantity;
    }

    /** Human-friendly unique number, e.g. SO-2026-0007. */
    public static function generateNumber(string $type): string
    {
        $prefix = $type === self::TYPE_PURCHASE ? 'PO' : 'SO';
        $year = date('Y');
        $next = (int) static::where('type', $type)->count() + 1;

        do {
            $number = sprintf('%s-%s-%04d', $prefix, $year, $next++);
        } while (static::where('order_number', $number)->exists());

        return $number;
    }

    /** Printable receipt number created when an admin approves the order. */
    public static function generateReceiptNumber(): string
    {
        $year = date('Y');
        $next = (int) static::whereNotNull('receipt_number')->count() + 1;

        do {
            $number = sprintf('RCP-%s-%04d', $year, $next++);
        } while (static::where('receipt_number', $number)->exists());

        return $number;
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PURCHASE => 'Purchase Order',
            self::TYPE_SALES => 'Sales Order',
        ];
    }
}
