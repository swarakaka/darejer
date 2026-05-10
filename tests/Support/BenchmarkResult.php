<?php

declare(strict_types=1);

namespace Darejer\Tests\Support;

use PHPUnit\Framework\Assert;

final class BenchmarkResult
{
    public function __construct(
        public readonly string $label,
        public readonly float $elapsedMs,
        public readonly int $memoryBytes,
        /** @var list<array{query: string, bindings: array, time: float}> */
        public readonly array $queries,
        public readonly mixed $value,
    ) {}

    public function queryCount(): int
    {
        return count($this->queries);
    }

    public function assertQueriesAtMost(int $max): self
    {
        Assert::assertLessThanOrEqual(
            $max,
            $this->queryCount(),
            sprintf(
                "[%s] expected ≤ %d queries, ran %d.\nSlowest queries:\n%s",
                $this->label,
                $max,
                $this->queryCount(),
                $this->slowestQueriesText(5),
            ),
        );

        return $this;
    }

    public function assertFasterThan(float $maxMs): self
    {
        Assert::assertLessThan(
            $maxMs,
            $this->elapsedMs,
            sprintf('[%s] expected < %.1f ms, took %.1f ms.', $this->label, $maxMs, $this->elapsedMs),
        );

        return $this;
    }

    public function report(): self
    {
        $line = sprintf(
            "    bench %-50s %6.1f ms  %4d queries  %5.1f kB\n",
            $this->label,
            $this->elapsedMs,
            $this->queryCount(),
            $this->memoryBytes / 1024,
        );

        if (defined('STDERR')) {
            fwrite(STDERR, $line);
        }

        return $this;
    }

    private function slowestQueriesText(int $limit): string
    {
        $sorted = $this->queries;
        usort($sorted, static fn ($a, $b) => ($b['time'] ?? 0) <=> ($a['time'] ?? 0));

        $lines = [];
        foreach (array_slice($sorted, 0, $limit) as $i => $q) {
            $lines[] = sprintf('  #%d  %.2fms  %s', $i + 1, $q['time'] ?? 0, $q['query']);
        }

        return implode("\n", $lines);
    }
}
