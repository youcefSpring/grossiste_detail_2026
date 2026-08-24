<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Counts the queries each page runs, so an N+1 shows up here rather than in a shop.
 * Usage: php artisan app:probe-queries
 */
class ProbeQueries extends Command
{
    protected $signature = 'app:probe-queries {--email=admin@grossiste.dz} {--page= : only this page, listing repeated queries}';

    protected $description = 'Report query count and time for each main page';

    private const PAGES = [
        'dashboard' => '/',
        'products' => '/products',
        'inventory' => '/inventory',
        'movements' => '/inventory/movements',
        'sales' => '/sales',
        'purchases' => '/purchases',
        'customers' => '/customers',
        'suppliers' => '/suppliers',
        'returns' => '/returns',
        'expenses' => '/expenses',
        'audit' => '/audit',
        'pos' => '/pos',
        'report:sales_day' => '/reports/sales_day',
        'report:sales_product' => '/reports/sales_product',
        'report:inventory' => '/reports/inventory',
        'report:financial' => '/reports/financial',
    ];

    public function handle(Kernel $kernel): int
    {
        $user = User::firstWhere('email', $this->option('email'));

        if (! $user) {
            $this->error('No such user.');

            return self::FAILURE;
        }

        $rows = [];
        $count = 0;
        $time = 0.0;
        $seen = [];

        // Register the listener once; resetting per page keeps the counts honest.
        DB::listen(function ($query) use (&$count, &$time, &$seen) {
            $count++;
            $time += $query->time;
            $seen[$query->sql] = ($seen[$query->sql] ?? 0) + 1;
        });

        $pages = $this->option('page')
            ? array_intersect_key(self::PAGES, [$this->option('page') => true])
            : self::PAGES;

        foreach ($pages as $label => $uri) {
            $count = 0;
            $time = 0.0;
            $seen = [];

            auth()->login($user);

            $request = Request::create($uri, 'GET');
            $request->setUserResolver(fn () => $user);

            $started = microtime(true);
            $response = $kernel->handle($request);
            $ms = (microtime(true) - $started) * 1000;

            $rows[] = [
                $label,
                $response->getStatusCode(),
                $count,
                number_format($time, 1).' ms',
                number_format($ms).' ms',
                $count > 25 ? '⚠ check' : 'ok',
            ];
        }

        $this->table(['page', 'status', 'queries', 'sql time', 'total', ''], $rows);

        if ($this->option('page')) {
            arsort($seen);

            foreach (array_slice($seen, 0, 5, true) as $sql => $times) {
                $this->line(sprintf('%3d×  %s', $times, substr($sql, 0, 110)));
            }
        }

        return self::SUCCESS;
    }
}
