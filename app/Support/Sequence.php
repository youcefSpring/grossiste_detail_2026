<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

class Sequence
{
    /**
     * Atomic per-year counter, e.g. ACH-2026-00042.
     * Row-locked so two employees confirming at once cannot collide.
     */
    public static function next(string $prefix): string
    {
        $year = now()->year;

        return DB::transaction(function () use ($prefix, $year) {
            $row = DB::table('sequences')
                ->where('name', $prefix)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                DB::table('sequences')->insert([
                    'name' => $prefix, 'year' => $year, 'current' => 0,
                    'created_at' => now(), 'updated_at' => now(),
                ]);

                $row = DB::table('sequences')
                    ->where('name', $prefix)->where('year', $year)->lockForUpdate()->first();
            }

            $next = $row->current + 1;

            DB::table('sequences')
                ->where('id', $row->id)
                ->update(['current' => $next, 'updated_at' => now()]);

            return sprintf('%s-%d-%05d', $prefix, $year, $next);
        });
    }
}
