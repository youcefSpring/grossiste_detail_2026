<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
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

    public function test_manager_can_create_a_product_with_opening_stock(): void
    {
        $this->actingAsRole('manager');
        $category = Category::create(['name' => 'Boissons']);

        $this->post(route('products.store'), [
            'name' => 'Eau 1.5L',
            'category_id' => $category->id,
            'barcode' => '6130000000017',
            'unit' => 'piece',
            'cost_price' => 30,
            'retail_price' => 45,
            'wholesale_price' => 38,
            'stock' => 120,
            'min_stock' => 24,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));

        $product = Product::query()->first();

        $this->assertSame('Eau 1.5L', $product->name);
        // Prices land in the DB as centimes.
        $this->assertSame(4500, (int) $product->retail_price);
        $this->assertSame(3000, (int) $product->cost_price);
        $this->assertSame(120.0, (float) $product->stock);
        $this->assertSame('opening', $product->movements()->sole()->type);
    }

    public function test_editing_stock_records_an_adjustment_movement(): void
    {
        $this->actingAsRole('manager');
        $product = Product::create(['name' => 'Sucre 1kg', 'unit' => 'kg', 'retail_price' => 12000]);
        app(StockService::class)->setQuantity($product, 50, 'opening');

        $this->put(route('products.update', $product), [
            'name' => 'Sucre 1kg',
            'unit' => 'kg',
            'cost_price' => 100,
            'retail_price' => 120,
            'wholesale_price' => 110,
            'stock' => 42,
            'min_stock' => 10,
            'is_active' => 1,
        ])->assertRedirect(route('products.index'));

        $this->assertSame(42.0, (float) $product->fresh()->inventory()->sum('quantity'));

        $adjustment = $product->movements()->where('type', 'adjustment')->sole();
        $this->assertSame(-8.0, (float) $adjustment->quantity);
        $this->assertSame(42.0, (float) $adjustment->balance_after);
    }

    public function test_stock_status_reflects_the_alert_level(): void
    {
        $this->actingAsRole('manager');
        $product = Product::create(['name' => 'Riz', 'unit' => 'kg', 'min_stock' => 10]);
        $stock = app(StockService::class);

        $stock->setQuantity($product, 50, 'opening');
        $this->assertSame('ok', $product->fresh()->stock_status);

        $stock->setQuantity($product, 8);
        $this->assertSame('low', $product->fresh()->stock_status);

        $stock->setQuantity($product, 0);
        $this->assertSame('out', $product->fresh()->stock_status);
    }

    public function test_sales_employee_cannot_create_products(): void
    {
        $this->actingAsRole('sales');

        $this->get(route('products.index'))->assertOk();
        $this->get(route('products.create'))->assertForbidden();
        $this->post(route('products.store'), ['name' => 'X'])->assertForbidden();
    }

    public function test_barcode_must_be_unique(): void
    {
        $this->actingAsRole('manager');
        Product::create(['name' => 'A', 'unit' => 'piece', 'barcode' => '123']);

        $this->post(route('products.store'), [
            'name' => 'B', 'unit' => 'piece', 'barcode' => '123',
            'cost_price' => 1, 'retail_price' => 2, 'wholesale_price' => 2,
            'stock' => 0, 'min_stock' => 0,
        ])->assertSessionHasErrors('barcode');
    }

    public function test_search_endpoint_finds_by_barcode_and_name(): void
    {
        $this->actingAsRole('sales');
        Product::create(['name' => 'Chocolat', 'unit' => 'piece', 'barcode' => '999', 'retail_price' => 5000]);

        $this->getJson(route('products.search', ['q' => '999']))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Chocolat');

        $this->getJson(route('products.search', ['q' => 'choco']))
            ->assertOk()
            ->assertJsonCount(1);
    }
}
