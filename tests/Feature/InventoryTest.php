<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryTest extends TestCase
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

    private function product(string $name, float $stock, float $minStock = 10): Product
    {
        $product = Product::create(['name' => $name, 'unit' => 'piece', 'min_stock' => $minStock, 'is_active' => true]);

        if ($stock > 0) {
            app(StockService::class)->setQuantity($product, $stock, 'opening');
        }

        return $product;
    }

    public function test_the_low_and_out_filters_pick_the_right_products(): void
    {
        $this->actingAsRole('warehouse');
        $this->product('Available', 50);
        $this->product('Low', 4);
        $this->product('Out', 0);

        $this->get(route('inventory.index'))
            ->assertOk()->assertSee('Available')->assertSee('Low')->assertSee('Out');

        $this->get(route('inventory.index', ['status' => 'low']))
            ->assertSee('Low')->assertDontSee('Available');

        $this->get(route('inventory.index', ['status' => 'out']))
            ->assertSee('Out')->assertDontSee('Low');
    }

    public function test_a_stock_count_writes_a_movement_with_the_reason(): void
    {
        $this->actingAsRole('warehouse');
        $product = $this->product('Riz 5kg', 50);

        $this->put(route('inventory.update', $product), [
            'quantity' => 47,
            'reason' => 'count',
            'note' => 'inventaire du soir',
        ])->assertRedirect(route('inventory.index'));

        $this->assertSame(47.0, (float) $product->inventory()->sum('quantity'));

        $movement = $product->movements()->where('type', 'adjustment')->sole();
        $this->assertSame(-3.0, (float) $movement->quantity);
        $this->assertSame(47.0, (float) $movement->balance_after);
        $this->assertStringContainsString('inventaire du soir', $movement->reason);
    }

    public function test_an_unchanged_count_records_nothing(): void
    {
        $this->actingAsRole('warehouse');
        $product = $this->product('Sucre', 30);

        $this->put(route('inventory.update', $product), ['quantity' => 30, 'reason' => 'count']);

        $this->assertSame(0, $product->movements()->where('type', 'adjustment')->count());
    }

    public function test_the_reason_is_required_and_must_be_known(): void
    {
        $this->actingAsRole('warehouse');
        $product = $this->product('Huile', 20);

        $this->put(route('inventory.update', $product), ['quantity' => 5])
            ->assertSessionHasErrors('reason');

        $this->put(route('inventory.update', $product), ['quantity' => 5, 'reason' => 'whatever'])
            ->assertSessionHasErrors('reason');
    }

    public function test_a_sales_employee_can_look_but_not_adjust(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product('Eau', 20);

        $this->get(route('inventory.index'))->assertOk();
        $this->get(route('inventory.movements'))->assertOk();
        $this->get(route('inventory.adjust', $product))->assertForbidden();
        $this->put(route('inventory.update', $product), ['quantity' => 1, 'reason' => 'count'])->assertForbidden();
    }

    public function test_the_movement_ledger_can_be_filtered_by_product_and_type(): void
    {
        $this->actingAsRole('warehouse');
        $rice = $this->product('Riz', 40);
        $this->product('Farine', 10);

        $this->get(route('inventory.movements', ['product_id' => $rice->id]))
            ->assertSee('Riz')->assertDontSee('Farine');

        $this->get(route('inventory.movements', ['type' => 'adjustment']))
            ->assertSee(__('stock.no_movements'));
    }

    public function test_the_dashboard_lists_products_needing_a_refill(): void
    {
        $this->actingAsRole('manager');
        $this->product('Bien fourni', 100);
        $this->product('Presque fini', 2);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Presque fini')
            ->assertDontSee('Bien fourni');
    }
}
