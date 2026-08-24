<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        Warehouse::firstOrCreate(
            ['is_default' => true],
            ['name' => 'المخزن الرئيسي', 'is_active' => true],
        );

        $categories = [
            'مواد غذائية', 'مشروبات', 'منظفات', 'أدوات منزلية',
            'ملابس', 'قرطاسية', 'مواد بناء', 'قطع غيار',
        ];

        foreach ($categories as $name) {
            Category::firstOrCreate(['name' => $name]);
        }

        $expenseCategories = [
            'كراء', 'كهرباء وماء', 'هاتف وأنترنت', 'نقل',
            'أجور', 'صيانة', 'ضرائب ورسوم', 'مصاريف أخرى',
        ];

        foreach ($expenseCategories as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
