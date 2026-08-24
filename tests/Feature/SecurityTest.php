<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $this->seed(RolesSeeder::class);
        Warehouse::firstOrCreate(['is_default' => true], ['name' => 'Main']);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function product(): Product
    {
        $product = Product::create([
            'name' => 'Lait 1L', 'unit' => 'piece',
            'cost_price' => 8000, 'retail_price' => 10000, 'wholesale_price' => 9000,
        ]);

        app(StockService::class)->setQuantity($product, 50, 'opening');

        return $product;
    }

    public function test_a_cashier_cannot_read_buying_prices_through_the_pos_search(): void
    {
        $this->actingAsRole('sales');
        $this->product();

        $this->getJson(route('products.search', ['q' => 'Lait']))
            ->assertOk()
            ->assertJsonPath('0.retail_price_raw', '100.00')
            ->assertJsonPath('0.cost_price_raw', null);
    }

    public function test_a_buyer_does_see_buying_prices(): void
    {
        $this->actingAsRole('purchasing');
        $this->product();

        $this->getJson(route('products.search', ['q' => 'Lait']))
            ->assertOk()
            ->assertJsonPath('0.cost_price_raw', '80.00');
    }

    public function test_the_profit_card_is_hidden_from_staff_without_financial_rights(): void
    {
        $this->actingAsRole('sales');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(__('dashboard.today_profit'))
            ->assertDontSee(__('dashboard.supplier_debts'));

        $this->actingAsRole('manager');

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('dashboard.today_profit'));
    }

    public function test_the_stock_keeper_sees_neither_money_nor_the_pos(): void
    {
        $this->actingAsRole('warehouse');

        $this->get(route('dashboard'))->assertOk()->assertDontSee(__('dashboard.today_sales'));
        $this->get(route('sales.create'))->assertForbidden();
        $this->get(route('reports.show', 'financial'))->assertForbidden();
        $this->get(route('customers.index'))->assertForbidden();
    }

    public function test_mass_assignment_cannot_forge_a_balance_or_a_stock_level(): void
    {
        $this->actingAsRole('manager');

        $this->post(route('customers.store'), [
            'name' => 'Malicieux', 'balance' => -999999, 'is_wholesale' => 0, 'is_active' => 1,
        ])->assertRedirect();

        $this->assertSame(0, (int) Customer::sole()->balance);

        $this->post(route('products.store'), [
            'name' => 'Faux stock', 'unit' => 'piece',
            'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15,
            'stock' => 0, 'min_stock' => 0, 'is_active' => 1,
        ])->assertRedirect();

        // Stock only ever moves through StockService, never straight off a form field.
        $this->assertSame(0.0, (float) Product::where('name', 'Faux stock')->sole()->stock);
    }

    public function test_an_executable_upload_is_rejected(): void
    {
        Storage::fake('public');
        $this->actingAsRole('manager');

        $this->post(route('products.store'), [
            'name' => 'Avec fichier', 'unit' => 'piece',
            'cost_price' => 10, 'retail_price' => 20, 'wholesale_price' => 15,
            'stock' => 0, 'min_stock' => 0, 'is_active' => 1,
            'image' => UploadedFile::fake()->create('shell.php', 20, 'application/x-php'),
        ])->assertSessionHasErrors('image');

        $this->assertSame(0, Product::count());
    }

    public function test_login_is_rate_limited(): void
    {
        $this->seed(RolesSeeder::class);
        $user = User::factory()->create(['is_active' => true, 'password' => 'motdepasse123']);

        foreach (range(1, 10) as $attempt) {
            $this->post(route('login'), ['email' => $user->email, 'password' => 'faux']);
        }

        $this->post(route('login'), ['email' => $user->email, 'password' => 'motdepasse123'])
            ->assertStatus(429);
    }

    public function test_a_guest_is_bounced_off_every_module(): void
    {
        $this->seed(RolesSeeder::class);

        foreach (['dashboard', 'products.index', 'inventory.index', 'sales.index', 'sales.create',
            'purchases.index', 'customers.index', 'suppliers.index', 'expenses.index',
            'reports.index', 'returns.index', 'users.index', 'settings.edit', 'audit.index'] as $route) {
            $this->get(route($route))->assertRedirect(route('login'));
        }
    }

    public function test_the_cached_stock_total_always_matches_the_ledger(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Test']);

        $this->post(route('sales.store'), [
            'customer_id' => $customer->id, 'type' => 'retail', 'method' => 'cash',
            'discount_amount' => 0, 'paid_amount' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        $sale = Sale::with('items')->sole();

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'damaged']],
            'refund_method' => 'credit',
        ]);

        $product->refresh();

        $ledger = (float) $product->movements()->sum('quantity');
        $inventory = (float) $product->inventory()->sum('quantity');

        $this->assertSame($ledger, (float) $product->stock);
        $this->assertSame($inventory, (float) $product->stock);
        $this->assertSame(45.0, (float) $product->stock);   // 50 − 5 sold + 2 back − 2 written off
    }

    public function test_the_recompute_command_reports_a_clean_ledger(): void
    {
        $this->actingAsRole('manager');
        $this->product();

        $this->artisan('app:recompute-stock --check')->assertSuccessful();
    }
}
