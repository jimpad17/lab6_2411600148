<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $brand = Brand::create(['name' => 'Test Brand', 'slug' => 'test-brand']);
        $category = Category::create(['name' => 'Test Category', 'slug' => 'test-category']);

        return Product::create(array_merge([
            'name' => 'Test Product',
            'sku' => 'JT-TEST-001',
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'reorder_level' => 5,
            'unit_price' => 1000,
        ], $attributes));
    }

    public function test_deactivating_a_product_keeps_the_record_and_allows_restore(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = $this->product();

        $this->actingAs($admin)
            ->delete(route('products.destroy', $product))
            ->assertRedirect(route('products.index'));

        // Nothing was deleted - the row is still present, just unavailable.
        $this->assertNotNull(Product::find($product->id));
        $this->assertFalse($product->fresh()->is_active);

        $this->actingAs($admin)
            ->post(route('products.restore', $product))
            ->assertRedirect(route('products.index'));

        $this->assertTrue($product->fresh()->is_active);
    }

    public function test_stock_movement_updates_quantity_and_creates_transaction(): void
    {
        $user = User::factory()->create();
        $product = $this->product();

        $this->actingAs($user)->post(route('inventory.stock', $product), [
            'type' => 'in',
            'quantity' => 5,
        ])->assertRedirect();

        $this->assertEquals(15, $product->fresh()->quantity);
        $this->assertDatabaseHas('inventory_transactions', [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 5,
        ]);
    }

    public function test_page_visits_and_searches_are_written_to_the_activity_log(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'viewed',
            'module' => 'Dashboard',
        ]);

        $this->actingAs($user)->get(route('products.index', ['search' => 'dumbbell']))->assertOk();
        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'action' => 'searched',
            'module' => 'Products',
        ]);

        $this->assertGreaterThanOrEqual(2, ActivityLog::where('user_id', $user->id)->count());
    }

    public function test_staff_accounts_cannot_open_admin_only_user_management(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('users.index'))->assertForbidden();
    }

    public function test_staff_can_view_and_post_stock_movements_but_cannot_change_master_data(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);
        $product = $this->product();

        $this->actingAs($staff)->get(route('dashboard'))->assertOk();
        $this->actingAs($staff)->get(route('inventory.index'))->assertOk();
        $this->actingAs($staff)->get(route('products.index'))->assertOk();
        $this->actingAs($staff)->get(route('products.show', $product))->assertOk();
        $this->actingAs($staff)->get(route('brands.index'))->assertOk();
        $this->actingAs($staff)->get(route('categories.index'))->assertOk();

        $this->actingAs($staff)->get(route('products.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('products.edit', $product))->assertForbidden();
        $this->actingAs($staff)->delete(route('products.destroy', $product))->assertForbidden();
        $this->actingAs($staff)->get(route('brands.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('categories.create'))->assertForbidden();
    }

    public function test_staff_cannot_open_admin_only_reports_and_activity_log(): void
    {
        $staff = User::factory()->create(['role' => 'staff']);

        $this->actingAs($staff)->get(route('reports.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('reports.export'))->assertForbidden();
        $this->actingAs($staff)->get(route('activity-logs.index'))->assertForbidden();
    }

    public function test_admin_can_open_every_module(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (['dashboard', 'inventory.index', 'products.index', 'brands.index',
                  'categories.index', 'users.index', 'reports.index', 'activity-logs.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_default_admin_account_cannot_be_deactivated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $protected = User::factory()->create([
            'role' => 'admin',
            'name' => 'ADMIN',
            'email' => User::PROTECTED_ADMIN_EMAIL,
        ]);

        $this->actingAs($admin)
            ->delete(route('users.destroy', $protected))
            ->assertRedirect()
            ->assertSessionHas('error', 'The default ADMIN account cannot be deactivated.');

        $this->actingAs($admin)
            ->put(route('users.update', $protected), [
                'name' => 'ADMIN',
                'email' => User::PROTECTED_ADMIN_EMAIL,
                'role' => 'admin',
                'is_active' => false,
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'The default ADMIN account cannot be deactivated.');

        $this->assertTrue($protected->fresh()->is_active);
    }

    public function test_random_or_broken_url_returns_to_the_last_real_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('products.index'))->assertOk();
        $this->actingAs($user)->get('/products//////.......//////')
            ->assertRedirect(route('products.index'));

        // Repeated broken URLs must not overwrite the stored page.
        $this->actingAs($user)->get('//////.......//////')
            ->assertRedirect(route('products.index'));
    }

    public function test_random_url_for_a_guest_returns_to_login(): void
    {
        $this->get('//////.......//////')->assertRedirect(route('login'));
    }
}
