<?php

namespace Database\Seeders;

use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Observers\InventoryTransactionObserver;
use Illuminate\Database\Seeder;

class InventoryTransactionSeeder extends Seeder
{
    /**
     * Give each seeded product an initial "Stock In" movement.
     */
    public function run(): void
    {
        $userId = User::where('email', 'admin@jimtrack.local')->value('id');

        // Historical rows already include the product's current quantity, so
        // the Lab 6 observer must not apply them a second time.
        InventoryTransactionObserver::withoutStockUpdates(function () use ($userId): void {
            Product::chunkById(100, function ($products) use ($userId) {
                foreach ($products as $product) {
                    InventoryTransaction::firstOrCreate(
                        [
                            'product_id' => $product->id,
                            'reference_number' => 'SEED-INITIAL',
                        ],
                        [
                            'type' => 'in',
                            'direction' => 'in',
                            'quantity' => $product->quantity,
                            'balance_after' => $product->quantity,
                            'user_id' => $userId,
                        ]
                    );
                }
            });
        });
    }
}
