<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Notifications\OrderApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AppMenuTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function staff(): User
    {
        return User::factory()->create(['role' => 'staff', 'is_active' => true]);
    }

    private function product(): Product
    {
        static $sequence = 0;
        $sequence++;

        $brand = Brand::create(['name' => 'Menu Brand '.$sequence, 'slug' => 'menu-brand-'.$sequence]);
        $category = Category::create(['name' => 'Menu Category '.$sequence, 'slug' => 'menu-category-'.$sequence]);

        return Product::create([
            'name' => 'Menu Product '.$sequence,
            'sku' => 'MNU-'.$sequence,
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'quantity' => 10,
            'reorder_level' => 3,
            'unit_price' => 250,
            'is_active' => true,
        ]);
    }

    public function test_admin_sidebar_uses_the_requested_menu_groups(): void
    {
        $response = $this->actingAs($this->admin())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Main')
            ->assertSee('Inventory')
            ->assertSee('Management')
            ->assertSee('Orders')
            ->assertSee('Records')
            ->assertSee('Monitoring')
            ->assertSee('Sales Order')
            ->assertSee('Purchase Order')
            ->assertSee('Transaction History')
            ->assertSee('Product History')
            ->assertSee('Daily Report History')
            ->assertSee('Audit Trail');
    }

    public function test_staff_sidebar_shows_only_the_staff_subset(): void
    {
        $response = $this->actingAs($this->staff())->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Product Report')
            ->assertSee('Sales Order')
            ->assertSee('Purchase Order')
            ->assertSee('Transaction History')
            ->assertSee('Product History')
            ->assertSee('Audit Trail')
            ->assertDontSee('>Users</a>', false)
            ->assertDontSee('Daily Report History');
    }

    public function test_admin_report_and_history_pages_render(): void
    {
        $this->product();
        $admin = $this->admin();

        foreach (['product-report', 'daily-report', 'daily-report-history', 'product-history'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_staff_cannot_open_the_admin_only_daily_report_history(): void
    {
        $this->actingAs($this->staff())->get(route('daily-report-history'))->assertForbidden();
    }

    public function test_dedicated_sales_and_purchase_order_pages_only_show_their_type(): void
    {
        $staff = $this->staff();
        $product = $this->product();

        foreach ([Order::TYPE_SALES, Order::TYPE_PURCHASE] as $type) {
            $this->actingAs($staff)->post(route('orders.store'), [
                'type' => $type,
                'product_id' => $product->id,
                'quantity' => 1,
                'order_date' => today()->toDateString(),
            ])->assertRedirect();
        }

        $this->actingAs($staff)->get(route('sales-orders'))
            ->assertOk()->assertSee('SO-', false);
        $this->actingAs($staff)->get(route('purchase-orders'))
            ->assertOk()->assertSee('PO-', false);

        $this->assertSame(1, Order::sales()->count());
        $this->assertSame(1, Order::purchases()->count());
    }

    public function test_approval_generates_a_printable_receipt(): void
    {
        $admin = $this->admin();
        $staff = $this->staff();
        $product = $this->product();

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 4,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->assertNull($order->receipt_number);

        $this->actingAs($admin)->post(route('orders.approve', $order))->assertRedirect();

        $order->refresh();
        $this->assertNotNull($order->receipt_number);
        $this->assertStringStartsWith('RCP-', $order->receipt_number);

        $this->actingAs($admin)->get(route('receipts.index'))
            ->assertOk()->assertSee($order->receipt_number);

        $this->assertSame(250.0, $order->unit_price);
        $this->assertSame(1000.0, $order->total);

        $this->actingAs($admin)->get(route('receipts.show', $order))
            ->assertOk()->assertSee('RECEIPT')->assertSee($order->receipt_number)
            ->assertSee('250.00')->assertSee('1,000.00');
    }

    public function test_staff_can_only_open_their_own_receipts(): void
    {
        $owner = $this->staff();
        $other = $this->staff();
        $product = $this->product();
        $admin = $this->admin();

        $this->actingAs($owner)->post(route('orders.store'), [
            'type' => Order::TYPE_SALES,
            'product_id' => $product->id,
            'quantity' => 1,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->actingAs($admin)->post(route('orders.approve', $order))->assertRedirect();

        $this->actingAs($other)->get(route('receipts.show', $order))->assertForbidden();
        $this->actingAs($owner)->get(route('receipts.show', $order))->assertOk();
    }

    public function test_approval_notifies_staff_and_admin_with_a_receipt_link(): void
    {
        Notification::fake();
        $admin = $this->admin();
        $staff = $this->staff();
        $product = $this->product();

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_PURCHASE,
            'product_id' => $product->id,
            'quantity' => 3,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $order = Order::firstOrFail();

        $this->actingAs($admin)->post(route('orders.approve', $order))->assertRedirect();
        $order->refresh();
        $this->assertNotNull($order->receipt_number);

        Notification::assertSentTo($staff, OrderApprovedNotification::class,
            function (OrderApprovedNotification $notification) use ($order, $staff) {
                $data = $notification->toArray($staff);

                return $data['receipt_number'] === $order->receipt_number
                    && $data['url'] === route('receipts.show', $order);
            });
        Notification::assertSentTo($admin, OrderApprovedNotification::class);
    }

    public function test_receipt_notification_appears_in_the_bell_and_inbox(): void
    {
        $admin = $this->admin();
        $staff = $this->staff();
        $product = $this->product();

        $this->actingAs($staff)->post(route('orders.store'), [
            'type' => Order::TYPE_SALES,
            'product_id' => $product->id,
            'quantity' => 2,
            'order_date' => today()->toDateString(),
        ])->assertRedirect();

        $order = Order::firstOrFail();
        $this->actingAs($admin)->post(route('orders.approve', $order))->assertRedirect();
        $order->refresh();

        $this->actingAs($staff)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Order Approved — Receipt Ready');

        $this->actingAs($staff)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($order->receipt_number)
            ->assertSee('View / Print Receipt');
    }

    public function test_user_can_edit_their_profile_and_upload_an_avatar(): void
    {
        Storage::fake('public');
        $staff = $this->staff();

        $this->actingAs($staff)->put(route('profile.update'), [
            'name' => 'Juan Updated',
            'email' => $staff->email,
            'avatar' => UploadedFile::fake()->createWithContent(
                'avatar.gif',
                base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7')
            ),
        ])->assertRedirect();

        $staff->refresh();
        $this->assertSame('Juan Updated', $staff->name);
        $this->assertNotNull($staff->avatar);
        Storage::disk('public')->assertExists($staff->avatar);
    }

    public function test_protected_admin_email_cannot_be_changed_from_the_profile(): void
    {
        $admin = $this->admin();
        $admin->update(['email' => User::PROTECTED_ADMIN_EMAIL]);

        $this->actingAs($admin)->put(route('profile.update'), [
            'name' => $admin->name,
            'email' => 'changed@example.com',
        ])->assertRedirect();

        $this->assertSame(User::PROTECTED_ADMIN_EMAIL, $admin->fresh()->email);
    }

    public function test_user_can_change_password_with_the_current_password(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'password' => Hash::make('current-pass'),
        ]);

        $this->actingAs($staff)->post(route('profile.password'), [
            'current_password' => 'current-pass',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertRedirect();

        $this->assertTrue(Hash::check('new-password', $staff->fresh()->password));
    }

    public function test_wrong_current_password_is_rejected(): void
    {
        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
            'password' => Hash::make('current-pass'),
        ]);

        $this->actingAs($staff)->post(route('profile.password'), [
            'current_password' => 'wrong-password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ])->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('current-pass', $staff->fresh()->password));
    }
}
