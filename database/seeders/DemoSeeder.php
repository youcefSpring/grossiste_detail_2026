<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\PurchaseService;
use App\Services\SaleService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Realistic demo data for a wholesale/retail shop.
 * Run with: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    private const PRODUCTS = [
        ['مواد غذائية', 'زيت المائدة 5ل', 780, 850, 810],
        ['مواد غذائية', 'سميد ممتاز 10كغ', 620, 700, 660],
        ['مواد غذائية', 'سكر أبيض 1كغ', 95, 120, 108],
        ['مواد غذائية', 'قهوة مطحونة 250غ', 240, 300, 275],
        ['مواد غذائية', 'شاي أخضر 200غ', 180, 230, 210],
        ['مواد غذائية', 'معجون طماطم 400غ', 85, 110, 98],
        ['مشروبات', 'ماء معدني 1.5ل', 25, 35, 30],
        ['مشروبات', 'مشروب غازي 2ل', 110, 150, 135],
        ['مشروبات', 'عصير برتقال 1ل', 90, 125, 110],
        ['منظفات', 'ماء جافيل 2ل', 95, 130, 115],
        ['منظفات', 'مسحوق غسيل 5كغ', 480, 590, 545],
        ['منظفات', 'صابون سائل 1ل', 130, 175, 158],
        ['أدوات منزلية', 'إسفنج مطبخ (12)', 140, 200, 175],
        ['أدوات منزلية', 'أكياس قمامة (30)', 110, 155, 138],
        ['قرطاسية', 'كراس 96 ورقة', 55, 80, 68],
        ['قرطاسية', 'أقلام جافة (10)', 90, 140, 118],
    ];

    private const SUPPLIERS = [
        ['مؤسسة بن عمر للجملة', '0550112233'],
        ['شركة الأمين للتوزيع', '0661445566'],
        ['مخازن الوسط', '0770998877'],
    ];

    private const CUSTOMERS = [
        ['محل الأمل', '0661223344', true, 50000],
        ['سوبيرات النور', '0770334455', true, 100000],
        ['مقهى الصداقة', '0550667788', true, 30000],
        ['أحمد بن يوسف', '0661889900', false, 0],
        ['فاطمة الزهراء', '0770112244', false, 0],
    ];

    public function run(): void
    {
        $warehouse = Warehouse::firstOrCreate(['is_default' => true], ['name' => 'المخزن الرئيسي']);
        $owner = User::firstWhere('email', 'admin@grossiste.dz') ?? User::first();
        auth()->login($owner);

        $suppliers = collect(self::SUPPLIERS)->map(fn ($row) => Supplier::firstOrCreate(
            ['name' => $row[0]],
            ['phone' => $row[1], 'is_active' => true],
        ));

        $customers = collect(self::CUSTOMERS)->map(fn ($row) => Customer::firstOrCreate(
            ['name' => $row[0]],
            ['phone' => $row[1], 'is_wholesale' => $row[2], 'credit_limit' => $row[3] * 100, 'is_active' => true],
        ));

        $products = collect(self::PRODUCTS)->map(function ($row) {
            $category = Category::firstOrCreate(['name' => $row[0]]);

            return Product::firstOrCreate(
                ['name' => $row[1]],
                [
                    'category_id' => $category->id,
                    'barcode' => (string) fake()->unique()->numerify('613#########'),
                    'unit' => 'piece',
                    'cost_price' => $row[2] * 100,
                    'retail_price' => $row[3] * 100,
                    'wholesale_price' => $row[4] * 100,
                    'min_stock' => 20,
                    'is_active' => true,
                ],
            );
        });

        // Opening stock so the first purchases and sales have something to work with.
        $stock = app(StockService::class);

        foreach ($products as $product) {
            $stock->setQuantity($product, random_int(40, 300), 'opening', $warehouse);
        }

        $this->buy($products, $suppliers, $warehouse);
        $this->sell($products, $customers);

        auth()->logout();

        $this->command->info('Demo data ready. Log in as admin@grossiste.dz / password');
    }

    private function buy($products, $suppliers, $warehouse): void
    {
        $purchases = app(PurchaseService::class);

        foreach (range(1, 12) as $index) {
            $items = $products->random(random_int(2, 5))->map(fn (Product $product) => [
                'product_id' => $product->id,
                'quantity' => random_int(10, 60),
                'unit_cost' => (int) $product->cost_price,
            ])->all();

            $total = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_cost']);

            $purchases->create([
                'supplier_id' => $suppliers->random()->id,
                'purchased_at' => now()->subDays(30 - $index * 2)->toDateString(),
                'discount_amount' => 0,
                // Some invoices are left partly unpaid — that is the normal state of a shop.
                'paid_amount' => $index % 3 === 0 ? (int) ($total * 0.6) : $total,
                'method' => 'cash',
                'items' => $items,
            ]);
        }
    }

    private function sell($products, $customers): void
    {
        $sales = app(SaleService::class);

        foreach (range(1, 60) as $index) {
            $customer = $index % 3 === 0 ? $customers->random() : null;
            $type = $customer?->is_wholesale ? 'wholesale' : 'retail';

            $items = $products->random(random_int(1, 4))->map(function (Product $product) use ($type) {
                $price = $type === 'wholesale' ? $product->wholesale_price : $product->retail_price;

                return [
                    'product_id' => $product->id,
                    'quantity' => random_int(1, $type === 'wholesale' ? 12 : 4),
                    'unit_price' => (int) $price,
                ];
            })->all();

            $total = collect($items)->sum(fn ($item) => $item['quantity'] * $item['unit_price']);

            try {
                $sale = $sales->create([
                    'customer_id' => $customer?->id,
                    'type' => $type,
                    'discount_amount' => 0,
                    'paid_amount' => $customer && $index % 4 === 0 ? (int) ($total * 0.5) : $total,
                    'method' => 'cash',
                    'items' => $items,
                ]);

                // Backdate so the reports have a spread of days to show.
                DB::table('sales')->where('id', $sale->id)
                    ->update(['sold_at' => now()->subDays(random_int(0, 20))->setTime(random_int(8, 19), random_int(0, 59))]);
            } catch (\Throwable) {
                // Stock ran out or a credit limit was hit — exactly what the guards are for.
                continue;
            }
        }
    }
}
