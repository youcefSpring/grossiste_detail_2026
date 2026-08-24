<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\ReportService;
use App\Services\StockService;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
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

    private function product(string $name = 'Lait 1L'): Product
    {
        $product = Product::create([
            'name' => $name, 'unit' => 'piece',
            'cost_price' => 8000, 'retail_price' => 10000, 'wholesale_price' => 9000,
        ]);

        app(StockService::class)->setQuantity($product, 100, 'opening');

        return $product;
    }

    /** 5 × 100,00 = 500,00 with 400,00 of cost */
    private function sell(Product $product, ?Customer $customer = null, float $paid = 500): Sale
    {
        $this->post(route('sales.store'), [
            'customer_id' => $customer?->id,
            'type' => 'retail', 'method' => 'cash',
            'discount_amount' => 0, 'paid_amount' => $paid,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
        ]);

        return Sale::latest('id')->with('items')->first();
    }

    public function test_an_expense_is_recorded_and_totalled(): void
    {
        $this->actingAsRole('accountant');
        $category = ExpenseCategory::create(['name' => 'Kiraa']);

        $this->post(route('expenses.store'), [
            'expense_category_id' => $category->id,
            'amount' => 15000,
            'method' => 'cash',
            'spent_at' => now()->toDateString(),
            'description' => 'loyer du mois',
        ])->assertRedirect(route('expenses.index'));

        $expense = Expense::sole();
        $this->assertSame(1500000, (int) $expense->amount);   // 15 000,00 DZD

        $this->get(route('expenses.index'))->assertOk()->assertSee('15 000,00');
    }

    public function test_a_sales_employee_cannot_touch_expenses(): void
    {
        $this->actingAsRole('sales');

        $this->get(route('expenses.index'))->assertForbidden();
        $this->post(route('expenses.store'), ['amount' => 100])->assertForbidden();
    }

    public function test_the_daily_sales_report_nets_revenue_cost_and_profit(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $this->sell($product);
        $this->sell($product);

        $rows = app(ReportService::class)->salesByDay(now()->startOfDay(), now());
        $today = $rows->first();

        $this->assertSame(2, $today->sales_count);
        $this->assertSame(100000, $today->revenue);   // 2 × 500,00
        $this->assertSame(80000, $today->cost);
        $this->assertSame(20000, $today->profit);

        $this->get(route('reports.show', 'sales_day'))->assertOk()->assertSee('1 000,00');
    }

    public function test_a_voided_sale_is_left_out_of_the_reports(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $sale = $this->sell($product);
        $this->sell($product);

        $this->post(route('sales.void', $sale), ['reason' => 'test']);

        $today = app(ReportService::class)->salesByDay(now()->startOfDay(), now())->first();
        $this->assertSame(1, $today->sales_count);
        $this->assertSame(50000, $today->revenue);
    }

    public function test_the_product_report_subtracts_returned_quantities(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $sale = $this->sell($product);

        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ]);

        $row = app(ReportService::class)->salesByProduct(now()->startOfDay(), now())->sole();

        $this->assertSame(3.0, $row->quantity);          // 5 sold, 2 back
        $this->assertSame(30000, $row->revenue);
        $this->assertSame(6000, $row->profit);
    }

    public function test_the_employee_report_names_the_seller(): void
    {
        $this->actingAsRole('manager');
        $this->sell($this->product());

        $row = app(ReportService::class)->salesByEmployee(now()->startOfDay(), now())->sole();

        $this->assertSame('Karim', $row->employee);
        $this->assertSame(1, $row->sales_count);
        $this->assertSame(50000, $row->revenue);
    }

    public function test_stock_valuation_uses_cost_and_retail(): void
    {
        $this->actingAsRole('manager');
        $this->product();

        $row = app(ReportService::class)->inventoryValuation()->first();

        // Values come straight from SQL, so they arrive as numeric strings.
        $this->assertSame(100.0, (float) $row->quantity);
        $this->assertSame(800000, (int) $row->cost_value);      // 100 × 80,00
        $this->assertSame(1000000, (int) $row->retail_value);   // 100 × 100,00
    }

    public function test_the_financial_summary_subtracts_returns_and_expenses(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $sale = $this->sell($product);

        // Give 1 back for cash: 100,00 out.
        $this->post(route('returns.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 1, 'condition' => 'resellable']],
            'refund_method' => 'cash',
        ]);

        Expense::create([
            'amount' => 5000, 'method' => 'cash', 'spent_at' => now()->toDateString(),
        ]);

        $summary = app(ReportService::class)->financialSummary(now()->startOfDay(), now());

        $this->assertSame(50000, $summary->revenue);
        $this->assertSame(40000, $summary->cost);
        $this->assertSame(10000, $summary->returns);
        $this->assertSame(0, $summary->gross_profit);      // 500,00 − 400,00 − 100,00
        $this->assertSame(5000, $summary->expenses);
        $this->assertSame(-5000, $summary->net_profit);    // a loss, and it says so
    }

    public function test_an_exchange_return_is_not_counted_as_a_refund(): void
    {
        $this->actingAsRole('manager');
        $product = $this->product();
        $other = $this->product('Yaourt');
        $sale = $this->sell($product);

        $this->post(route('exchanges.store', $sale), [
            'items' => [['sale_item_id' => $sale->items->first()->id, 'quantity' => 2, 'condition' => 'resellable']],
            'new_items' => [['product_id' => $other->id, 'quantity' => 2, 'unit_price' => 100]],
            'extra_paid' => 0, 'method' => 'cash',
        ]);

        // The goods swapped; no money left the till, so returns stay at zero.
        $this->assertSame(0, app(ReportService::class)->financialSummary(now()->startOfDay(), now())->returns);
    }

    public function test_the_report_menu_only_lists_what_the_role_may_open(): void
    {
        $this->actingAsRole('warehouse');

        $this->get(route('reports.index'))
            ->assertOk()
            ->assertSee(__('report.names.inventory'))
            ->assertDontSee(__('report.names.financial'));

        $this->get(route('reports.show', 'financial'))->assertForbidden();
        $this->get(route('reports.show', 'inventory'))->assertOk();
    }

    public function test_an_unknown_report_is_a_404(): void
    {
        $this->actingAsRole('owner');

        $this->get(route('reports.show', 'nonsense'))->assertNotFound();
    }

    public function test_a_report_exports_as_csv(): void
    {
        $this->actingAsRole('manager');
        $this->sell($this->product());

        $response = $this->get(route('reports.show', ['sales_day', 'export' => 'csv']));

        $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);      // Excel needs the BOM for Arabic
        $this->assertStringContainsString(__('report.columns.sales_day.revenue'), $csv);
        $this->assertStringContainsString('500,00', $csv);

        // A count of 1 invoice must stay "1" — not be formatted as 0,01.
        $this->assertStringContainsString(';1;', $csv);
    }

    public function test_a_report_exports_as_a_pdf(): void
    {
        $this->actingAsRole('manager');
        $this->sell($this->product());

        $response = $this->get(route('reports.show', ['sales_day', 'export' => 'pdf']));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public function test_an_invoice_exports_as_a_pdf(): void
    {
        $this->actingAsRole('sales');
        $product = $this->product('Café Moulu');
        $sale = $this->sell($product);

        $response = $this->get(route('sales.invoice', [$sale, 'export' => 'pdf']));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
