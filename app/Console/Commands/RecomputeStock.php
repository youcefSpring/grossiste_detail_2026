<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Safety net: rebuild every product's cached stock total from the inventory rows.
 * The two are kept in step inside StockService, so this should never find a difference —
 * run it after a restore, a manual SQL edit, or simply to prove the books agree.
 */
class RecomputeStock extends Command
{
    protected $signature = 'app:recompute-stock {--check : report differences without fixing them}';

    protected $description = 'Rebuild cached product stock totals from the inventory ledger';

    public function handle(): int
    {
        $mismatches = Product::query()
            ->selectRaw('products.id, products.name, products.stock as cached,
                         coalesce((select sum(quantity) from inventory where inventory.product_id = products.id), 0) as actual')
            ->havingRaw('cached <> actual')
            ->get();

        if ($mismatches->isEmpty()) {
            $this->info('All product stock totals agree with the inventory ledger.');

            return self::SUCCESS;
        }

        $this->table(['id', 'product', 'cached', 'actual'], $mismatches->map(fn ($row) => [
            $row->id, $row->name, $row->cached, $row->actual,
        ]));

        if ($this->option('check')) {
            $this->warn($mismatches->count().' product(s) differ. Run without --check to fix.');

            return self::FAILURE;
        }

        DB::statement('
            update products
            set stock = coalesce((select sum(quantity) from inventory where inventory.product_id = products.id), 0)
        ');

        $this->info('Fixed '.$mismatches->count().' product(s).');

        return self::SUCCESS;
    }
}
