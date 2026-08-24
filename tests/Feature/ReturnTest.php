<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Inventory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseReturn;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnTest extends TestCase
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

    private function product(string $name = 'Lait 1L', float $stock = 100): Product
    {
        $product = Product::create([
            'name' => $name,
            'unit' => 'piece',
            'cost_price' => 8000,
            'retail_price' => 10000,
            'wholesale_price' => 9000,
        ]);

        app(StockService::class)->setQuantity($product, $stock, 'opening');

        return $product;
    }

    /** Sell 5 @ 100,00 = 500,00 */
    private function sell(Product $product, ?Customer $customer = null, float $paid = 500): Sale
    {
        $this->post(route('sales.store'), [
            'customer_id' => $customer?->id,
            'type' => 'retail',
            'method' => 'cash',
            'discount_amount' => 0,
            'paid_amount' => $paid,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        return Sale::latest('id')->with('items')->first();
    }

    public function test_a_resellable_return_puts_the_stock_back_and_refunds_cash(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->assertSame(95.0, (float) Product::query()->sole()->stock);

        $this->post(route('returns.store', $sale), [
            'items' => [[
                'sale_item_id' => $sale->items->first()->id,
                'quantity' => 2,
                'condition' => 'resellable',
            ]],
            'refund_method' => 'cash',
            'reason' => 'ne convient pas',
        ])->assertRedirect();

        $return = SaleReturn::with('items')->sole();

        $this->assertSame('RET-'.now()->year.'-00001', $return->reference);
        $this->assertSame(20000, (int) $return->total_amount);       // 2 × 100,00
        $this->assertSame(97.0, (float) Product::query()->sole()->stock);
        $this->assertSame(2.0, (float) $sale->items->first()->fresh()->returned_quantity);
        $this->assertSame('partially_returned', $sale->fresh()->status);

        $refund = Payment::where('direction', 'out')->sole();
        $this->assertSame(20000, (int) $refund->amount);
        $this->assertSame(SaleReturn::class, $refund->payable_type);
    }

    public function test_damaged_goods_do_not_go_back_on_the_shelf(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->post(route('returns.store', $sale), [
            'items' => [[
                'sale_item_id' => $sale->items->first()->id,
                'quantity' => 2,
                'condition' => 'damaged',
            ]],
            'refund_method' => 'cash',
        ]);

        // Sellable stock is untouched; the damage is tracked separately.
        $this->assertSame(95.0, (float) Product::query()->sole()->stock);
        $this->assertSame(2.0, (float) Inventory::sole()->damaged_quantity);

        // The ledger explains it: goods in, then written off.
        $types = $product->movements()->orderBy('id')->pluck('type')->all();
        $this->assertSame(['opening', 'sale', 'sale_return', 'damaged'], $types);
    }

    public function test_a_credit_refund_lowers_what_the_customer_owes(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Sofiane']);
        $sale = $this->sell($product, $customer, paid: 0);

        $this->assertSame(50000, (int) $customer->fresh()->balance);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'resellable']],
            'refund_method' => 'credit',
        ]);

        $this->assertSame(30000, (int) $customer->fresh()->balance);
        $this->assertSame(0, Payment::where('direction', 'out')->count());
    }

    public function test_returning_everything_marks_the_sale_returned(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 5, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ]);

        $this->assertSame('returned', $sale->fresh()->status);
        $this->assertSame(100.0, (float) Product::query()->sole()->stock);
    }

    public function test_you_cannot_return_more_than_was_sold(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 6, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, SaleReturn::count());
        $this->assertSame(95.0, (float) Product::query()->sole()->stock);
    }

    public function test_two_partial_returns_cannot_exceed_the_sold_quantity(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $sale = $this->sell($product);
        $itemId = $sale->items->first()->id;

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $itemId, 'quantity' => 3, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ]);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $itemId, 'quantity' => 3, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ])->assertSessionHasErrors('items');

        $this->assertSame(1, SaleReturn::count());
        $this->assertSame(98.0, (float) Product::query()->sole()->stock);
    }

    public function test_nothing_can_be_returned_from_a_voided_sale(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->post(route('sales.void', $sale), ['reason' => 'test']);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ])->assertSessionHasErrors('sale');

        $this->assertSame(0, SaleReturn::count());
    }

    public function test_an_exchange_for_a_dearer_product_asks_the_customer_for_the_difference(): void
    {
        $this->actingAsRole('sales');
        $cheap = $this->product('Lait 1L');
        $dear = Product::create([
            'name' => 'Fromage', 'unit' => 'piece',
            'cost_price' => 20000, 'retail_price' => 25000, 'wholesale_price' => 24000,
        ]);
        app(StockService::class)->setQuantity($dear, 50, 'opening');

        $sale = $this->sell($cheap);

        // Give back 2 × 100,00 = 200,00; take 1 × 250,00. Difference: 50,00.
        $this->post(route('exchanges.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'resellable']],
            'new_items' => [['product_id' => $dear->id, 'quantity' => 1, 'unit_price' => 250]],
            'extra_paid' => 50,
            'method' => 'cash',
        ])->assertRedirect();

        $return = SaleReturn::with('exchangeSale')->sole();
        $newSale = $return->exchangeSale;

        $this->assertTrue($return->isExchange());
        $this->assertSame(20000, (int) $return->total_amount);
        $this->assertSame(25000, (int) $newSale->total);
        $this->assertSame(25000, (int) $newSale->paid_amount);   // 200,00 credit + 50,00 cash
        $this->assertSame(0, (int) $newSale->due_amount);

        // Stock: old one back on the shelf, new one gone out.
        $this->assertSame(97.0, (float) Product::query()->find($cheap->id)->stock);
        $this->assertSame(49.0, (float) Product::query()->find($dear->id)->stock);

        // Two payment rows: the returned goods, then the cash top-up.
        $methods = Payment::where('payable_id', $newSale->id)->pluck('method')->sort()->values()->all();
        $this->assertSame(['cash', 'exchange'], $methods);
    }

    public function test_an_exchange_for_a_cheaper_product_leaves_the_customer_in_credit(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product();
        $customer = Customer::create(['name' => 'Amina']);
        $sale = $this->sell($product, $customer);

        $cheap = Product::create(['name' => 'Yaourt', 'unit' => 'piece', 'cost_price' => 2000, 'retail_price' => 3000]);
        app(StockService::class)->setQuantity($cheap, 30, 'opening');

        // Back: 2 × 100,00 = 200,00. Out: 1 × 30,00. The shop owes 170,00.
        $this->post(route('exchanges.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'resellable']],
            'new_items' => [['product_id' => $cheap->id, 'quantity' => 1, 'unit_price' => 30]],
            'extra_paid' => 0,
            'method' => 'cash',
        ]);

        $newSale = SaleReturn::sole()->exchangeSale;

        $this->assertSame(3000, (int) $newSale->total);
        $this->assertSame(3000, (int) $newSale->paid_amount);
        $this->assertSame(0, (int) $newSale->due_amount);
    }

    public function test_a_supplier_return_takes_stock_out_and_lowers_the_debt(): void
    {
        $this->actingAsRole('purchasing');
        $supplier = Supplier::create(['name' => 'Grossiste Oran']);
        $product = $this->product(stock: 0);

        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_at' => now()->toDateString(),
            'method' => 'cash',
            'discount_amount' => 0,
            'paid_amount' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 80]],
        ]);

        $purchase = Purchase::with('items')->sole();
        $this->assertSame(160000, (int) $supplier->fresh()->balance);
        $this->assertSame(20.0, (float) Product::query()->sole()->stock);

        $this->post(route('purchase-returns.store', $purchase), [
            'items' => [['purchase_item_id' => $purchase->items->first()->id, 'quantity' => 5]],
            'reason' => 'produit périmé',
        ])->assertRedirect(route('purchases.show', $purchase));

        $return = PurchaseReturn::sole();
        $this->assertSame('RETF-'.now()->year.'-00001', $return->reference);
        $this->assertSame(40000, (int) $return->total_amount);
        $this->assertSame(15.0, (float) Product::query()->sole()->stock);
        $this->assertSame(120000, (int) $supplier->fresh()->balance);
        $this->assertSame(5.0, (float) $purchase->items->first()->fresh()->returned_quantity);
    }

    public function test_you_cannot_return_more_to_the_supplier_than_you_bought(): void
    {
        $this->actingAsRole('purchasing');
        $supplier = Supplier::create(['name' => 'Grossiste Oran']);
        $product = $this->product(stock: 0);

        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_at' => now()->toDateString(),
            'method' => 'cash',
            'discount_amount' => 0,
            'paid_amount' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 20, 'unit_cost' => 80]],
        ]);

        $purchase = Purchase::with('items')->sole();

        $this->post(route('purchase-returns.store', $purchase), [
            'items' => [['purchase_item_id' => $purchase->items->first()->id, 'quantity' => 25]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseReturn::count());
        $this->assertSame(20.0, (float) Product::query()->sole()->stock);
    }

    public function test_a_supplier_return_is_blocked_when_the_goods_are_already_sold(): void
    {
        $this->actingAsRole('manager');
        $supplier = Supplier::create(['name' => 'Grossiste Oran']);
        $product = $this->product(stock: 0);

        $this->post(route('purchases.store'), [
            'supplier_id' => $supplier->id,
            'purchased_at' => now()->toDateString(),
            'method' => 'cash',
            'discount_amount' => 0, 'paid_amount' => 0,
            'items' => [['product_id' => $product->id, 'quantity' => 6, 'unit_cost' => 80]],
        ]);

        $this->sell($product);   // 5 of the 6 go out the door

        $purchase = Purchase::with('items')->sole();

        $this->post(route('purchase-returns.store', $purchase), [
            'items' => [['purchase_item_id' => $purchase->items->first()->id, 'quantity' => 4]],
        ])->assertSessionHasErrors('items');

        $this->assertSame(0, PurchaseReturn::count());
    }

    public function test_a_warehouse_employee_cannot_process_returns(): void
    {
        $this->actingAsRole('warehouse');

        $this->get(route('returns.index'))->assertForbidden();
        $this->get(route('returns.create'))->assertForbidden();
        $this->get(route('exchanges.create'))->assertForbidden();
    }
}
