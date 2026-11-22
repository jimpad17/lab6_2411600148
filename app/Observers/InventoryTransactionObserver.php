<?php

namespace App\Observers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Lab 6 - event-driven stock updates.
 *
 * A single source of truth: controllers only CREATE a transaction. This observer
 * applies the signed quantity to the product, records the running balance and
 * fires the low-stock alert. All of it happens inside one DB transaction, so a
 * failed quantity update rolls the movement back completely.
 */
class InventoryTransactionObserver
{
    /** Used by seeders when importing historical movements. */
    protected static bool $bypass = false;

    public static function withoutStockUpdates(callable $callback): mixed
    {
        $previous = static::$bypass;
        static::$bypass = true;

        try {
            return $callback();
        } finally {
            static::$bypass = $previous;
        }
    }

    public function created(InventoryTransaction $transaction): void
    {
        if (static::$bypass) {
            return;
        }

        DB::transaction(function () use ($transaction): void {
            /** @var Product|null $product */
            $product = Product::whereKey($transaction->product_id)->lockForUpdate()->first();

            if (! $product) {
                return;
            }

            $delta = $transaction->signedQuantity();
            $current = (int) $product->quantity;
            $newQuantity = $current + $delta;

            // Option A from the lab instructions: reject and roll back.
            if ($newQuantity < 0) {
                throw InsufficientStockException::forProduct(
                    $product->name,
                    $current,
                    abs($delta)
                );
            }

            // updateQuietly prevents recursive model events while still
            // keeping the stock figures atomic with the transaction row.
            $product->updateQuietly(['quantity' => $newQuantity]);
            $transaction->updateQuietly(['balance_after' => $newQuantity]);

            $this->handleLowStockAlert($product->fresh(), $newQuantity > $product->reorder_level);
        });
    }

    /**
     * Alert admins/managers once per low-stock episode; clear the marker when
     * the product is replenished above its reorder level.
     */
    private function handleLowStockAlert(?Product $product, bool $replenished): void
    {
        if (! $product || ! $product->is_active) {
            return;
        }

        if ($replenished || ! $product->isLowStock()) {
            if ($product->low_stock_notified_at !== null) {
                $product->updateQuietly(['low_stock_notified_at' => null]);
            }

            return;
        }

        if ($product->low_stock_notified_at
            && $product->low_stock_notified_at->gt(now()->subHours(24))) {
            return;
        }

        $recipients = User::active()->where('role', 'admin')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new LowStockNotification($product));
            $product->updateQuietly(['low_stock_notified_at' => now()]);
        } catch (Throwable $exception) {
            // An email problem must never block a valid stock movement.
            report($exception);
        }
    }
}
