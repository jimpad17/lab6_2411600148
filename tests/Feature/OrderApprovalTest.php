<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\StockOutNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderApprovalTest extends TestCase
{
    use RefreshDatabase;

    private static int $sequence = 0;

    private function product(array $attributes = []): Product
    {
        self::$sequence++;

        $brand = Brand::create([
            'name' => 'Order Brand '.self::$sequence,
            'slug' => 'order-brand-'.self::$sequence,
        ]);
        $category = Category::create([
            'name' => 'Order Category '.self::$sequence,
            'slug' => 'order-category-'.self::$sequence,
        ]);

        return Product::create(array_merge([
            'name' => 'Order Product '.self::$sequence,
            'sku' => 'JT-ORD-'.self::$sequence,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'reorder_level' => 3,
            'unit_price' => 500,
            'is_active' => true,
        ], $attributes));
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    public function test_login_screen_shows_the_new_admin_credentials(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('jimpadillo@gmail.com')
            ->assertSee('admin123');
    }

    public function test_admin_can_authenticate_with_the_new_email(): void
    {
        $this->seed(\Database\Seeders\UserSeeder::class);

        $this->post(route('login'), [
            'email' => 'jimpadillo@gmail.com',
            'password' => 'admin123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_staff_submits_a_pending_order_without_changing_stock(): void
    {
        $staff = $this->staff();
        $product = $this->product(['quantity' => 10]);

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 10,
            'order_date' => today()->toDateString(),
            'notes' => 'Initial count was wrong.',
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertSame(Order::STATUS_PENDING, $order->status);
        $this->assertSame($staff->id, $order->user_id);
        $this->assertEquals(10, $product->fresh()->quantity);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_admin_corrects_a_staff_mistake_then_approves_the_order(): void
    {
        $staff = $this->staff();
        $admin = $this->admin();
        $product = $this->product(['quantity' => 10]);

        // Staff mistakenly entered 10 instead of 5 (a sales order).
        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_SALES,
            'product_id' => $product->id,
            'quantity' => 10,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $order = Order::firstOrFail();

        // Admin fixes 10 -> 5 before anything is posted.
        $this->actingAs($admin)->put(route('orders.update', $order), [
            'type' => Order::TYPE_SALES,
            'product_id' => $product->id,
            'quantity' => 5,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $this->assertEquals(10, $order->fresh()->original_quantity);
        $this->assertEquals(5, $order->fresh()->quantity);
        $this->assertEquals(10, $product->fresh()->quantity, 'Stock must stay untouched until approval.');

        $this->actingAs($admin)->post(route('orders.approve', $order))
            ->assertRedirect();

        $this->assertSame(Order::STATUS_APPROVED, $order->fresh()->status);
        $this->assertEquals(5, $product->fresh()->quantity); // 10 - 5
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'out',
            'quantity' => 5,
            'reference_number' => $order->order_number,
        ]);
    }

    public function test_staff_cannot_edit_or_approve_orders(): void
    {
        $staff = $this->staff();
        $product = $this->product();
        $order = Order::create([
            'order_number' => Order::generateNumber(Order::TYPE_PURCHASE),
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 4,
            'order_date' => today(),
            'status' => Order::STATUS_PENDING,
            'user_id' => $staff->id,
        ]);

        $this->actingAs($staff)->get(route('orders.edit', $order))->assertForbidden();
        $this->actingAs($staff)->post(route('orders.approve', $order))->assertForbidden();
    }

    public function test_past_order_dates_are_rejected_but_future_dates_are_allowed(): void
    {
        $staff = $this->staff();
        $product = $this->product();

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 2,
            'order_date' => today()->subDay()->toDateString(),
        ])->assertSessionHasErrors(['order_date']);

        $this->assertDatabaseCount('orders', 0);

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 2,
            'order_date' => today()->addDay()->toDateString(),
        ])->assertRedirect();

        $this->assertDatabaseCount('orders', 1);
    }

    public function test_stockout_makes_product_unavailable_and_alerts_the_admin(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $product = $this->product(['quantity' => 3, 'reorder_level' => 2]);

        InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => 'out',
            'direction' => 'out',
            'quantity' => 3,
            'reference_number' => 'SO-STOCKOUT',
            'user_id' => $admin->id,
        ]);

        $fresh = $product->fresh();
        $this->assertEquals(0, $fresh->quantity);
        $this->assertFalse((bool) $fresh->is_active);
        $this->assertNotNull($fresh->stockout_notified_at);
        $this->assertSame('Unavailable', $fresh->stock_status);

        Notification::assertSentTo($admin, StockOutNotification::class);
        $this->assertDatabaseHas('activity_logs', [
            'module' => 'Inventory',
            'user_id' => $admin->id,
        ]);
        $this->assertTrue(ActivityLog::where('description', 'like', '%ran out of stock%')->exists());
    }

    public function test_restocking_restores_an_auto_unavailable_product(): void
    {
        $admin = $this->admin();
        $product = $this->product(['quantity' => 1, 'reorder_level' => 1]);

        InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => 'out', 'direction' => 'out', 'quantity' => 1,
            'user_id' => $admin->id,
        ]);
        $this->assertFalse((bool) $product->fresh()->is_active);

        InventoryTransaction::create([
            'product_id' => $product->id,
            'type' => 'in', 'direction' => 'in', 'quantity' => 20,
            'user_id' => $admin->id,
        ]);

        $this->assertTrue((bool) $product->fresh()->is_active);
        $this->assertNull($product->fresh()->stockout_notified_at);
        $this->assertEquals(20, $product->fresh()->quantity);
    }

    public function test_product_id_accepts_letters_and_numbers(): void
    {
        $admin = $this->admin();
        $this->product(); // creates a brand and category for the form validation
        $brand = Brand::firstOrFail();
        $category = Category::firstOrFail();

        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Alphanumeric Sku Product',
            'sku' => 'ABC123',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 1,
            'reorder_level' => 1,
            'unit_price' => 100,
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['sku' => 'ABC123']);

        $this->actingAs($admin)->post(route('products.store'), [
            'name' => 'Invalid Sku Product',
            'sku' => 'BAD SKU!',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 1,
            'reorder_level' => 1,
            'unit_price' => 100,
        ])->assertSessionHasErrors(['sku']);
    }

    public function test_role_specific_dashboard_sections_render(): void
    {
        $product = $this->product();
        Order::create([
            'order_number' => 'SO-TEST-0001',
            'type' => Order::TYPE_SALES,
            'product_id' => $product->id,
            'quantity' => 2,
            'order_date' => today(),
            'status' => Order::STATUS_APPROVED,
            'user_id' => $this->staff()->id,
        ]);

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Sales Report')
            ->assertSee('Purchase Report')
            ->assertSee('Daily Report History')
            ->assertSee('Product History');

        $this->actingAs($this->staff())->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Product Report')
            ->assertSee('My Orders')
            ->assertDontSee('Daily Report History');
    }
}
