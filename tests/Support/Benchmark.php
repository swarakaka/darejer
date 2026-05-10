<?php

declare(strict_types=1);

namespace Darejer\Tests\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Pest benchmark helper for the darejer package — captures wall time,
 * peak memory delta and DB query log around a closure. Wall times are
 * relative (in-memory SQLite); query counts are the real signal.
 */
final class Benchmark
{
    public static function run(string $label, Closure $fn): BenchmarkResult
    {
        foreach (DB::getConnections() as $connection) {
            $connection->flushQueryLog();
            $connection->enableQueryLog();
        }

        $memBefore = memory_get_usage();
        $startNs = hrtime(true);

        $value = $fn();

        $elapsedNs = hrtime(true) - $startNs;
        $memAfter = memory_get_peak_usage();

        $queries = [];
        foreach (DB::getConnections() as $connection) {
            foreach ($connection->getQueryLog() as $entry) {
                $queries[] = $entry;
            }
            $connection->disableQueryLog();
        }

        return new BenchmarkResult(
            label: $label,
            elapsedMs: $elapsedNs / 1_000_000,
            memoryBytes: max(0, $memAfter - $memBefore),
            queries: $queries,
            value: $value,
        );
    }
}
