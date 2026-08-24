<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\Settings;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $this->seed(RolesSeeder::class);
        Warehouse::create(['name' => 'Main', 'is_default' => true]);

        $user = User::factory()->create(['is_active' => true, 'name' => 'Karim']);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    public function test_creating_a_product_is_recorded_with_who_did_it(): void
    {
        $user = $this->actingAsRole('manager');

        $this->post(route('products.store'), [
            'name' => 'Thé vert', 'unit' => 'piece',
            'cost_price' => 100, 'retail_price' => 150, 'wholesale_price' => 130,
            'stock' => 0, 'min_stock' => 5, 'is_active' => 1,
        ]);

        $log = AuditLog::where('auditable_type', Product::class)->where('action', 'created')->sole();

        $this->assertSame($user->id, $log->user_id);
        $this->assertSame('Thé vert', $log->label);
        $this->assertSame('15000', (string) $log->new_values['retail_price']);
    }

    public function test_an_update_keeps_both_the_old_and_the_new_value(): void
    {
        $this->actingAsRole('manager');
        $product = Product::create(['name' => 'Sucre', 'unit' => 'kg', 'retail_price' => 12000]);

        $this->put(route('products.update', $product), [
            'name' => 'Sucre 1kg', 'unit' => 'kg',
            'cost_price' => 100, 'retail_price' => 140, 'wholesale_price' => 130,
            'stock' => 0, 'min_stock' => 0, 'is_active' => 1,
        ]);

        $log = AuditLog::where('auditable_type', Product::class)->where('action', 'updated')->latest('id')->first();

        $this->assertSame('12000', (string) $log->old_values['retail_price']);
        $this->assertSame('14000', (string) $log->new_values['retail_price']);
        $this->assertSame('Sucre', $log->old_values['name']);
    }

    public function test_voiding_a_sale_leaves_a_trail(): void
    {
        $this->actingAsRole('manager');
        $product = Product::create(['name' => 'Lait', 'unit' => 'piece', 'cost_price' => 8000, 'retail_price' => 10000]);
        app(StockService::class)->setQuantity($product, 50, 'opening');

        $this->post(route('sales.store'), [
            'type' => 'retail', 'method' => 'cash', 'discount_amount' => 0, 'paid_amount' => 200,
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
        ]);

        $sale = Sale::sole();
        $this->post(route('sales.void', $sale), ['reason' => 'client parti']);

        $log = AuditLog::where('auditable_type', Sale::class)->where('action', 'updated')->latest('id')->first();

        $this->assertSame('voided', $log->new_values['status']);
        $this->assertSame('completed', $log->old_values['status']);
        $this->assertSame('client parti', $log->new_values['void_reason']);
        $this->assertSame($sale->invoice_number, $log->label);
    }

    public function test_passwords_never_reach_the_trail(): void
    {
        $this->actingAsRole('owner');

        $this->post(route('users.store'), [
            'name' => 'Nabil', 'email' => 'nabil@grossiste.dz', 'locale' => 'ar', 'role' => 'sales',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123', 'is_active' => 1,
        ]);

        $log = AuditLog::where('auditable_type', User::class)->where('action', 'created')->sole();

        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertStringNotContainsString('motdepasse', json_encode($log->new_values));
    }

    public function test_only_permitted_roles_may_read_the_trail(): void
    {
        $this->actingAsRole('manager');
        $this->get(route('audit.index'))->assertOk();

        $this->actingAsRole('sales');
        $this->get(route('audit.index'))->assertForbidden();
    }

    public function test_an_owner_can_create_a_user_with_a_role(): void
    {
        $this->actingAsRole('owner');

        $this->post(route('users.store'), [
            'name' => 'Salима', 'email' => 'salima@grossiste.dz', 'locale' => 'fr', 'role' => 'accountant',
            'password' => 'motdepasse123', 'password_confirmation' => 'motdepasse123', 'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', 'salima@grossiste.dz')->sole();

        $this->assertTrue($user->hasRole('accountant'));
        $this->assertTrue(Hash::check('motdepasse123', $user->password));
    }

    public function test_editing_a_user_without_a_password_keeps_the_old_one(): void
    {
        $owner = $this->actingAsRole('owner');
        $other = User::factory()->create(['is_active' => true, 'password' => 'ancienmotdepasse']);
        $other->assignRole('sales');

        $this->put(route('users.update', $other), [
            'name' => 'Nouveau nom', 'email' => $other->email, 'locale' => 'ar',
            'role' => 'warehouse', 'password' => '', 'is_active' => 1,
        ])->assertRedirect(route('users.index'));

        $other->refresh();
        $this->assertSame('Nouveau nom', $other->name);
        $this->assertTrue($other->hasRole('warehouse'));
        $this->assertTrue(Hash::check('ancienmotdepasse', $other->password));
    }

    public function test_the_only_owner_cannot_be_demoted(): void
    {
        $owner = $this->actingAsRole('owner');

        $this->put(route('users.update', $owner), [
            'name' => 'Karim', 'email' => $owner->email, 'locale' => 'ar',
            'role' => 'manager', 'password' => '', 'is_active' => 1,
        ])->assertSessionHasErrors('role');

        $this->assertTrue($owner->fresh()->hasRole('owner'));
    }

    public function test_an_owner_can_be_demoted_once_another_owner_exists(): void
    {
        $owner = $this->actingAsRole('owner');
        $second = User::factory()->create(['is_active' => true]);
        $second->assignRole('owner');

        $this->put(route('users.update', $second), [
            'name' => $second->name, 'email' => $second->email, 'locale' => 'ar',
            'role' => 'manager', 'password' => '', 'is_active' => 1,
        ])->assertSessionHasNoErrors();

        $this->assertTrue($second->fresh()->hasRole('manager'));
    }

    public function test_a_refused_demotion_changes_nothing_at_all(): void
    {
        $owner = $this->actingAsRole('owner');

        $this->put(route('users.update', $owner), [
            'name' => 'Nom modifié', 'email' => $owner->email, 'locale' => 'fr',
            'role' => 'sales', 'password' => '', 'is_active' => 1,
        ])->assertSessionHasErrors('role');

        // The name and language must not have slipped through with the rejected role.
        $owner->refresh();
        $this->assertSame('Karim', $owner->name);
        $this->assertSame('ar', $owner->locale);
    }

    public function test_nobody_can_disable_their_own_account(): void
    {
        $owner = $this->actingAsRole('owner');

        $this->post(route('users.toggle', $owner))->assertSessionHasErrors('user');
        $this->assertTrue($owner->fresh()->is_active);
    }

    public function test_a_disabled_user_is_refused_at_login(): void
    {
        $this->actingAsRole('owner');
        $other = User::factory()->create(['is_active' => true, 'password' => 'motdepasse123']);
        $other->assignRole('sales');

        $this->post(route('users.toggle', $other));

        auth()->logout();

        $this->post(route('login'), ['email' => $other->email, 'password' => 'motdepasse123'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_saving_settings_changes_what_the_shop_shows(): void
    {
        $this->actingAsRole('owner');

        $this->put(route('settings.update'), [
            'shop_name' => 'محل النور',
            'shop_phone' => '0550112233',
            'shop_address' => 'حي 1000 مسكن، وهران',
            'currency_symbol' => 'دج',
            'locale_default' => 'ar',
            'invoice_prefix' => 'FAC',
            'sale_default_type' => 'wholesale',
            'sale_max_discount_percent' => 10,
            'payment_methods' => ['cash', 'credit', 'cheque'],
            'allow_negative_stock' => 1,
        ])->assertRedirect(route('settings.edit'));

        $this->assertSame('محل النور', settings('shop.name'));
        $this->assertSame('FAC', settings('invoice.prefix'));
        $this->assertSame('wholesale', settings('sale.default_type'));
        $this->assertSame(['cash', 'credit', 'cheque'], settings('payment.methods'));
        $this->assertTrue(settings('allow_negative_stock'));

        // Toggles left out of the request go back to off.
        $this->assertFalse(settings('tax_enabled'));
    }

    public function test_the_invoice_prefix_setting_drives_new_invoice_numbers(): void
    {
        $this->actingAsRole('owner');
        Settings::set('invoice.prefix', 'FAC');

        $product = Product::create(['name' => 'Lait', 'unit' => 'piece', 'cost_price' => 8000, 'retail_price' => 10000]);
        app(StockService::class)->setQuantity($product, 10, 'opening');

        $this->post(route('sales.store'), [
            'type' => 'retail', 'method' => 'cash', 'discount_amount' => 0, 'paid_amount' => 100,
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
        ]);

        $this->assertSame('FAC-'.now()->year.'-00001', Sale::sole()->invoice_number);
    }

    public function test_an_invalid_prefix_is_rejected(): void
    {
        $this->actingAsRole('owner');

        $this->put(route('settings.update'), [
            'shop_name' => 'Shop', 'currency_symbol' => 'DA', 'locale_default' => 'ar',
            'invoice_prefix' => 'inv-2026', 'sale_default_type' => 'retail',
            'sale_max_discount_percent' => 5, 'payment_methods' => ['cash'],
        ])->assertSessionHasErrors('invoice_prefix');
    }

    public function test_a_manager_cannot_open_settings_or_users(): void
    {
        $this->actingAsRole('manager');

        $this->get(route('settings.edit'))->assertForbidden();
        $this->get(route('users.index'))->assertForbidden();
    }

    public function test_the_alert_bell_counts_what_needs_attention(): void
    {
        $this->actingAsRole('manager');

        $empty = Product::create(['name' => 'Rupture', 'unit' => 'piece', 'min_stock' => 5, 'is_active' => true]);
        $low = Product::create(['name' => 'Bientôt fini', 'unit' => 'piece', 'min_stock' => 10, 'is_active' => true]);
        app(StockService::class)->setQuantity($low, 3, 'opening');
        app(StockService::class)->setQuantity($empty, 0, 'opening');

        $alerts = collect(\App\Support\Alerts::for(auth()->user()))->keyBy('key');

        $this->assertSame(1, $alerts['out_of_stock']['count']);
        $this->assertSame(1, $alerts['low_stock']['count']);
    }
}
