<?php

namespace Database\Seeders;

use App\Support\Settings;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([RolesSeeder::class, CatalogSeeder::class]);

        foreach (Settings::DEFAULTS as $key => $value) {
            \App\Models\Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        $this->call(UserSeeder::class);
    }
}
