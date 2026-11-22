<?php

namespace Tests\Feature;

use App\Exceptions\InsufficientStockException;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class InventoryTrackingTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private function product(array $attributes = []): Product
    {
        self::$sequence++;

        $brand = Brand::create([
            'name' => 'Tracking Brand '.self::$sequence,
            'slug' => 'tracking-brand-'.self::$sequence,
        ]);
        $category = Category::create([
            'name' => 'Tracking Category '.self::$sequence,
            'slug' => 'tracking-category-'.self::$sequence,
        ]);

        return Product::create(array_merge([
            'name' => 'Tracking Product '.self::$sequence,
            'sku' => 'JT-TRK-'.self::$sequence,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'reorder_level' => 5,
            'unit_price' => 1000,
            'is_active' => true,
        ], $attributes));
    }

    private function movement(Product $product, string $type, int $quantity, ?string $direction = null): InventoryTransaction
    {
        return InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => $type,
            'direction' => $direction ?? ($type === 'out' ? 'out' : 'in'),
            'quantity' => $quantity,
            'reference_number' => 'TEST-'.strtoupper($type),
            'user_id' => auth()->id(),
        ]);
    }

    public function test_stock_in_transaction_increases_quantity_through_the_observer(): void
    {
        $product = $this->product(['quantity' => 10]);

        $transaction = $this->movement($product, 'in', 4);

        $this->assertEquals(14, $product->fresh()->quantity);
        $this->assertEquals(14, $transaction->fresh()->balance_after);
        $this->assertDatabaseHas('inventory_transactions', [
            'id' => $transaction->id,
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 4,
        ]);
    }

    public function test_stock_out_transaction_decreases_quantity_through_the_observer(): void
    {
        $product = $this->product(['quantity' => 10]);

        $transaction = $this->movement($product, 'out', 3);

        $this->assertEquals(7, $product->fresh()->quantity);
        $this->assertEquals(7, $transaction->fresh()->balance_after);
        $this->assertEquals('−3', $transaction->quantity_display);
    }

    public function test_overselling_is_rejected_and_the_whole_transaction_is_rolled_back(): void
    {
        $product = $this->product(['quantity' => 3]);

        try {
            DB::transaction(fn () => $this->movement($product, 'out', 5));
            $this->fail('Expected an InsufficientStockException.');
        } catch (InsufficientStockException $exception) {
            $this->assertStringContainsString('Not enough stock', $exception->getMessage());
        }

        $this->assertEquals(3, $product->fresh()->quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_controller_validation_rejects_insufficient_stock_before_saving(): void
    {
        $user = User::factory()->create();
        $product = $this->product(['quantity' => 2]);

        $this->actingAs($user)->post(route('transactions.store'), [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 9,
            'reference_number' => 'SO-1',
        ])->assertSessionHasErrors(['quantity']);

        $this->assertEquals(2, $product->fresh()->quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_low_stock_notification_is_sent_once_per_episode_to_active_admins(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $inactive = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $this->assertFalse((bool) $inactive->fresh()->is_active);
        $this->assertEquals(1, User::active()->where('role', 'admin')->count());
        $product = $this->product(['quantity' => 10, 'reorder_level' => 5]);

        $this->movement($product, 'out', 6); // 4 pcs -> low

        Notification::assertSentToTimes($admin, LowStockNotification::class, 1);
        Notification::assertSentTimes(LowStockNotification::class, 1);

        $this->movement($product, 'out', 1); // still low -> no duplicate
        Notification::assertSentTimes(LowStockNotification::class, 1);

        $this->movement($product, 'in', 10); // 13 pcs -> recovered
        $this->movement($product, 'out', 9); // 4 pcs -> low again
        Notification::assertSentTimes(LowStockNotification::class, 2);
    }

    public function test_adjustment_transaction_applies_the_correct_direction(): void
    {
        $product = $this->product(['quantity' => 8]);

        $increase = $this->movement($product, 'adjustment', 3, 'in');
        $this->assertEquals(11, $product->fresh()->quantity);
        $this->assertEquals('+3', $increase->quantity_display);

        $decrease = $this->movement($product, 'adjustment', 5, 'out');
        $this->assertEquals(6, $product->fresh()->quantity);
        $this->assertEquals('−5', $decrease->quantity_display);
    }

    public function test_reversing_a_transaction_restores_stock_and_keeps_the_audit_trail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['quantity' => 10]);
        $transaction = $this->actingAs($admin)->movement($product, 'out', 4);

        $this->actingAs($admin)->delete(route('transactions.destroy', $transaction))
            ->assertRedirect(route('transactions.index'));

        $this->assertEquals(10, $product->fresh()->quantity);
        $this->assertTrue((bool) $transaction->fresh()->is_reversed);
        $this->assertDatabaseCount('inventory_transactions', 2);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'reference_number' => 'REVERSAL-'.$transaction->id,
            'type' => 'in',
            'quantity' => 4,
        ]);
    }

    public function test_staff_cannot_reverse_a_transaction(): void
    {
        $product = $this->product();
        $transaction = $this->movement($product, 'in', 2);

        $this->actingAs(User::factory()->create(['role' => 'staff']))
            ->delete(route('transactions.destroy', $transaction))
            ->assertForbidden();

        $this->assertFalse((bool) $transaction->fresh()->is_reversed);
    }

    public function test_api_endpoints_return_live_json_statistics(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product(['quantity' => 2, 'reorder_level' => 5]);
        $this->movement($product, 'out', 1);

        $this->actingAs($admin)->getJson(route('api.dashboard.stats'))
            ->assertOk()
            ->assertJsonPath('data.total_products', 1)
            ->assertJsonPath('data.inventory_value', 1000)
            ->assertJsonStructure(['data' => ['total_products', 'low_stock', 'out_of_stock', 'generated_at']]);

        $this->actingAs($admin)->getJson(route('api.products.low-stock'))
            ->assertOk()
            ->assertJsonPath('data.0.sku', $product->sku);

        $this->actingAs($admin)->getJson(route('api.transactions.recent'))
            ->assertOk()
            ->assertJsonPath('data.0.product.name', $product->name);

        $this->actingAs($admin)->getJson(route('api.products.stock', $product))
            ->assertOk()
            ->assertJsonPath('data.quantity', 1);
    }

    public function test_guests_cannot_read_the_inventory_api(): void
    {
        $this->getJson(route('api.dashboard.stats'))->assertUnauthorized();
    }

    public function test_product_helpers_and_scopes_report_stock_health(): void
    {
        $healthy = $this->product(['quantity' => 20, 'reorder_level' => 5]);
        $low = $this->product(['quantity' => 3, 'reorder_level' => 5]);
        $out = $this->product(['quantity' => 0, 'reorder_level' => 5]);

        $this->assertTrue($healthy->isLowStock() === false);
        $this->assertTrue($low->isLowStock());
        $this->assertTrue($out->isOutOfStock());
        $this->assertSame('Low Stock', $low->stock_status);
        $this->assertSame('warning', $low->stock_status_color);
        $this->assertSame('danger', $out->stock_status_color);
        $this->assertEquals(2, Product::lowStock()->orWhere(function ($q) use ($out) {
            $q->where('products.id', $out->id);
        })->count());
        $this->assertEquals(2, Product::inCategory($low->category_id)->count() + 1);
    }

    public function test_transaction_list_and_product_history_pages_render(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();
        $this->movement($product, 'in', 5);

        $this->actingAs($admin)->get(route('transactions.index'))
            ->assertOk()->assertSee('Transaction Log')->assertSee('TEST-IN');

        $this->actingAs($admin)->get(route('transactions.index', ['type' => 'out']))
            ->assertOk()->assertSee('No stock movements match your filters.');

        $this->actingAs($admin)->get(route('transactions.show', InventoryTransaction::first()))
            ->assertOk()->assertSee('Running Balance');

        $this->actingAs($admin)->get(route('products.show', $product))
            ->assertOk()->assertSee('Transaction History')->assertSee('Running balance');

        $this->actingAs($admin)->get(route('reports.tracking'))
            ->assertOk()->assertSee('Stock Movement Report');
    }

    public function test_adjustment_page_requires_admin_role(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->get(route('adjustments.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('adjustments.create'))->assertOk()->assertSee('Physical Count Adjustment');
    }
}
