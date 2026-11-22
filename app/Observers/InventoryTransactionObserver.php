<?php

namespace App\Observers;

use App\Exceptions\InsufficientStockException;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use App\Notifications\StockOutNotification;
use App\Services\ActivityLogger;
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

            // Lab 6 extension: an outbound movement that empties the shelf
            // automatically makes the product unavailable. A later stock-in
            // restores it only when the deactivation came from a stockout.
            $updates = ['quantity' => $newQuantity];
            $isStockout = false;

            if ($delta < 0 && $newQuantity === 0) {
                $updates['is_active'] = false;
                $updates['stockout_notified_at'] = now();
                $isStockout = true;
            } elseif ($delta > 0 && $newQuantity > 0 && $product->stockout_notified_at !== null) {
                $updates['is_active'] = true;
                $updates['stockout_notified_at'] = null;
            }

            // updateQuietly prevents recursive model events while still
            // keeping the stock figures atomic with the transaction row.
            $product->updateQuietly($updates);
            $transaction->updateQuietly(['balance_after' => $newQuantity]);

            $fresh = $product->fresh();

            if ($isStockout) {
                $this->handleStockOut($fresh, $transaction);
            } else {
                $this->handleLowStockAlert($fresh, $newQuantity > $fresh->reorder_level);
            }
        });
    }

    /** Alert admins in the bell and write an audit entry when stock hits zero. */
    private function handleStockOut(Product $product, InventoryTransaction $transaction): void
    {
        ActivityLogger::log(
            'updated',
            'Inventory',
            "Product '{$product->name}' ({$product->sku}) ran out of stock and was automatically set to unavailable.",
            $transaction->user
        );

        $recipients = User::active()->where('role', 'admin')->get();

        if ($recipients->isEmpty()) {
            return;
        }

        try {
            Notification::send($recipients, new StockOutNotification($product));
        } catch (Throwable $exception) {
            report($exception);
        }
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
