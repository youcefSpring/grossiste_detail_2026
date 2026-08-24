<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseTest extends TestCase
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

    private function payload(array $overrides = []): array
    {
        $supplier = Supplier::create(['name' => 'Grossiste Alger']);
        $product = Product::create(['name' => 'Huile 5L', 'unit' => 'piece', 'cost_price' => 70000]);

        return array_merge([
            'supplier_id' => $supplier->id,
            'purchased_at' => now()->toDateString(),
            'method' => 'cash',
            'discount_amount' => 0,
            'paid_amount' => 0,
            'items' => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_cost' => 750],
            ],
        ], $overrides);
    }

    public function test_a_purchase_adds_stock_and_leaves_the_supplier_owed(): void
    {
        $this->actingAsRole('purchasing');

        $this->post(route('purchases.store'), $this->payload())
            ->assertRedirect();

        $purchase = Purchase::with('items')->sole();

        $this->assertSame('ACH-'.now()->year.'-00001', $purchase->reference);
        $this->assertSame(750000, (int) $purchase->total);      // 10 × 750,00 DZD in centimes
        $this->assertSame(750000, (int) $purchase->due_amount);

        $product = Product::query()->sole();
        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame(75000, (int) $product->cost_price);    // latest buying price wins

        $movement = $product->movements()->sole();
        $this->assertSame('purchase', $movement->type);
        $this->assertSame(Purchase::class, $movement->reference_type);
        $this->assertSame($purchase->id, $movement->reference_id);

        $this->assertSame(750000, (int) Supplier::sole()->balance);
    }

    public function test_paying_in_full_leaves_no_debt_and_records_the_payment(): void
    {
        $this->actingAsRole('purchasing');

        $this->post(route('purchases.store'), $this->payload(['paid_amount' => 7500]));

        $purchase = Purchase::sole();
        $this->assertSame(0, (int) $purchase->due_amount);
        $this->assertSame(0, (int) Supplier::sole()->balance);

        $payment = Payment::sole();
        $this->assertSame('out', $payment->direction);
        $this->assertSame('supplier', $payment->party_type);
        $this->assertSame(750000, (int) $payment->amount);
        $this->assertSame(Purchase::class, $payment->payable_type);
    }

    public function test_a_discount_lowers_the_total(): void
    {
        $this->actingAsRole('purchasing');

        $this->post(route('purchases.store'), $this->payload(['discount_amount' => 500]));

        $purchase = Purchase::sole();
        $this->assertSame(750000, (int) $purchase->subtotal);
        $this->assertSame(50000, (int) $purchase->discount_amount);
        $this->assertSame(700000, (int) $purchase->total);
    }

    public function test_overpaying_is_capped_at_the_total(): void
    {
        $this->actingAsRole('purchasing');

        $this->post(route('purchases.store'), $this->payload(['paid_amount' => 99999]));

        $purchase = Purchase::sole();
        $this->assertSame(750000, (int) $purchase->paid_amount);
        $this->assertSame(0, (int) $purchase->due_amount);
        $this->assertSame(0, (int) Supplier::sole()->balance);
    }

    public function test_a_purchase_needs_at_least_one_line(): void
    {
        $this->actingAsRole('purchasing');

        $this->post(route('purchases.store'), $this->payload(['items' => []]))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, Purchase::count());
    }

    public function test_nothing_is_written_when_a_line_is_invalid(): void
    {
        $this->actingAsRole('purchasing');
        $payload = $this->payload();
        $payload['items'][0]['quantity'] = 0;

        $this->post(route('purchases.store'), $payload)
            ->assertSessionHasErrors('items.0.quantity');

        $this->assertSame(0, Purchase::count());
        $this->assertSame(0, \App\Models\StockMovement::count());
    }

    public function test_paying_a_supplier_on_account_lowers_the_balance(): void
    {
        $this->actingAsRole('purchasing');
        $this->post(route('purchases.store'), $this->payload());

        $supplier = Supplier::sole();
        $this->assertSame(750000, (int) $supplier->balance);

        $this->post(route('suppliers.pay', $supplier), [
            'amount' => 3000,
            'method' => 'cash',
            'paid_at' => now()->toDateString(),
        ])->assertRedirect(route('suppliers.show', $supplier));

        $this->assertSame(450000, (int) $supplier->fresh()->balance);

        // The purchase itself was unpaid, so this on-account payment is the only one.
        $payment = Payment::sole();
        $this->assertSame(300000, (int) $payment->amount);
        $this->assertNull($payment->payable_type);
    }

    public function test_references_do_not_collide(): void
    {
        $this->actingAsRole('purchasing');
        $payload = $this->payload();

        $this->post(route('purchases.store'), $payload);
        $this->post(route('purchases.store'), $payload);

        $this->assertSame(
            ['ACH-'.now()->year.'-00001', 'ACH-'.now()->year.'-00002'],
            Purchase::orderBy('id')->pluck('reference')->all(),
        );
    }

    public function test_a_sales_employee_cannot_buy(): void
    {
        $this->actingAsRole('sales');

        $this->get(route('purchases.index'))->assertForbidden();
        $this->post(route('purchases.store'), $this->payload())->assertForbidden();
    }

    public function test_the_dashboard_shows_what_we_owe_suppliers(): void
    {
        $this->actingAsRole('manager');
        $this->post(route('purchases.store'), $this->payload());

        $this->get(route('dashboard'))->assertOk()->assertSee('7 500,00');
    }
}
