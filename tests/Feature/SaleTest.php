<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use App\Support\Settings;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsRole(string $role): User
    {
        $this->seed(RolesSeeder::class);
        Warehouse::create(['name' => 'Main', 'is_default' => true]);

        $user = User::factory()->create(['is_active' => true]);
        $user->assignRole($role);
        $this->actingAs($user);

        return $user;
    }

    private function product(array $attributes = [], float $stock = 100): Product
    {
        $product = Product::create(array_merge([
            'name' => 'Lait 1L',
            'unit' => 'piece',
            'cost_price' => 8000,       // 80,00
            'retail_price' => 10000,    // 100,00
            'wholesale_price' => 9000,  // 90,00
        ], $attributes));

        app(StockService::class)->setQuantity($product, $stock, 'opening');

        return $product;
    }

    private function payload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'type' => 'retail',
            'method' => 'cash',
            'discount_amount' => 0,
            'paid_amount' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 3, 'unit_price' => 100]],
        ], $overrides);
    }

    public function test_a_cash_sale_takes_stock_out_and_records_the_payment(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();

        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]))
            ->assertRedirect();

        $sale = Sale::with('items')->sole();

        $this->assertSame('INV-'.now()->year.'-00001', $sale->invoice_number);
        $this->assertSame(30000, (int) $sale->total);
        $this->assertSame(0, (int) $sale->due_amount);
        $this->assertSame(24000, (int) $sale->cost_total);   // 3 × 80,00
        $this->assertSame(6000, $sale->profit());

        $this->assertSame(97.0, (float) Product::query()->sole()->stock);

        $movement = $product->movements()->where('type', 'sale')->sole();
        $this->assertSame(-3.0, (float) $movement->quantity);
        $this->assertSame(Sale::class, $movement->reference_type);

        $this->assertSame(30000, (int) Payment::sole()->amount);
    }

    public function test_an_unpaid_sale_becomes_customer_debt(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Ahmed']);

        $this->post(route('sales.store'), $this->payload($product, [
            'customer_id' => $customer->id,
            'paid_amount' => 100,
        ]));

        $this->assertSame(20000, (int) Sale::sole()->due_amount);
        $this->assertSame(20000, (int) $customer->fresh()->balance);
        $this->assertSame(1, Payment::count());
    }

    public function test_a_walk_in_sale_creates_no_debt_row(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();

        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]));

        $this->assertNull(Sale::sole()->customer_id);
        $this->assertSame(0, Customer::count());
    }

    public function test_selling_more_than_the_shelf_holds_is_refused(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product(stock: 2);

        $this->post(route('sales.store'), $this->payload($product))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Sale::count());
        $this->assertSame(2.0, (float) Product::query()->sole()->stock);
    }

    public function test_a_price_below_the_floor_is_refused(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product(['min_price' => 9500]);

        $this->post(route('sales.store'), $this->payload($product, [
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 90]],
        ]))->assertSessionHasErrors('items');

        $this->assertSame(0, Sale::count());
    }

    public function test_a_cashier_cannot_exceed_the_discount_ceiling(): void
    {
        $this->actingAsRole('sales');
        Settings::set('sale.max_discount_percent', 5);
        $product = $this->product();

        // 300,00 subtotal, 5% ceiling = 15,00
        $this->post(route('sales.store'), $this->payload($product, ['discount_amount' => 50]))
            ->assertSessionHasErrors('discount_amount');

        $this->post(route('sales.store'), $this->payload($product, ['discount_amount' => 10]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(29000, (int) Sale::sole()->total);
    }

    public function test_a_manager_may_discount_freely(): void
    {
        $this->actingAsRole('manager');
        Settings::set('sale.max_discount_percent', 5);
        $product = $this->product();

        $this->post(route('sales.store'), $this->payload($product, ['discount_amount' => 100]))
            ->assertSessionDoesntHaveErrors();

        $this->assertSame(20000, (int) Sale::sole()->total);
    }

    public function test_a_customer_over_their_credit_limit_is_stopped(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Karim', 'credit_limit' => 25000]); // 250,00

        $this->post(route('sales.store'), $this->payload($product, ['customer_id' => $customer->id]))
            ->assertSessionHasErrors('paid_amount');

        $this->assertSame(0, Sale::count());
        $this->assertSame(0, (int) $customer->fresh()->balance);
        $this->assertSame(100.0, (float) Product::query()->sole()->stock);
    }

    public function test_a_wholesale_sale_uses_the_wholesale_price(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();

        $this->post(route('sales.store'), $this->payload($product, [
            'type' => 'wholesale',
            'items' => [['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 90]],
            'paid_amount' => 900,
        ]));

        $sale = Sale::sole();
        $this->assertSame('wholesale', $sale->type);
        $this->assertSame(90000, (int) $sale->total);
    }

    public function test_voiding_a_sale_returns_the_stock_and_the_debt(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Yacine']);

        $this->post(route('sales.store'), $this->payload($product, ['customer_id' => $customer->id]));
        $sale = Sale::sole();

        $this->assertSame(30000, (int) $customer->fresh()->balance);
        $this->assertSame(97.0, (float) Product::query()->sole()->stock);

        $this->post(route('sales.void', $sale), ['reason' => 'erreur de caisse'])
            ->assertRedirect(route('sales.show', $sale));

        $sale->refresh();
        $this->assertTrue($sale->isVoided());
        $this->assertSame('erreur de caisse', $sale->void_reason);
        $this->assertSame(100.0, (float) Product::query()->sole()->stock);
        $this->assertSame(0, (int) $customer->fresh()->balance);

        // Nothing is deleted — the sale and its lines stay on record.
        $this->assertSame(1, Sale::count());
        $this->assertSame(1, $sale->items()->count());
    }

    public function test_a_cashier_cannot_void(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]));

        $this->post(route('sales.void', Sale::sole()), ['reason' => 'x'])->assertForbidden();
    }

    public function test_collecting_a_debt_lowers_the_balance(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Nadia']);

        $this->post(route('sales.store'), $this->payload($product, ['customer_id' => $customer->id]));
        $this->assertSame(30000, (int) $customer->fresh()->balance);

        $this->post(route('customers.collect', $customer), [
            'amount' => 200,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame(10000, (int) $customer->fresh()->balance);
    }

    public function test_the_invoice_page_prints_the_shop_and_the_lines(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product(['name' => 'Café Moulu']);

        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]));

        $this->get(route('sales.invoice', Sale::sole()))
            ->assertOk()
            ->assertSee('Café Moulu')
            ->assertSee('INV-'.now()->year.'-00001');
    }

    public function test_the_dashboard_totals_today(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();

        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('300,00')   // today's sales
            ->assertSee('60,00');   // today's profit
    }

    public function test_a_voided_sale_is_left_out_of_the_day_total(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $this->post(route('sales.store'), $this->payload($product, ['paid_amount' => 300]));

        $this->post(route('sales.void', Sale::sole()), ['reason' => 'test']);

        $this->get(route('dashboard'))->assertOk()->assertDontSee('300,00');
    }
}
