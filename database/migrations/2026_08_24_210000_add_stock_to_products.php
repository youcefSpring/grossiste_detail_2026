<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The stock total lives on the product row, maintained by StockService inside the
     * same locked transaction as the inventory row. Deriving it per query meant grouping
     * the whole catalogue on every page; this makes it an indexed lookup.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->decimal('stock', 12, 3)->default(0)->after('min_stock');
            $table->index(['is_active', 'stock']);
        });

        DB::statement('
            update products
            set stock = coalesce((select sum(quantity) from inventory where inventory.product_id = products.id), 0)
        ');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_active', 'stock']);
            $table->dropColumn('stock');
        });
    }
};
