<?php

declare(strict_types=1);

namespace Darejer\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Writes a row to the host app's `audit_logs` table.
 *
 * The schema is owned by the host application (a single migration shipped
 * with the consuming app). The package only writes; the host is responsible
 * for creating, viewing and retaining the table.
 *
 * Schema contract (column → type):
 *   event         string(64)
 *   subject_type  string(191)  nullable
 *   subject_id    bigint       nullable
 *   company_id    fk(companies) nullable
 *   causer_id     fk(users)     nullable
 *   reason        text          nullable
 *   summary       string(500)   nullable
 *   payload       json          nullable
 *   ip            string(45)    nullable
 *   user_agent    string(255)   nullable
 *   created_at    timestamp
 */
final class AuditWriter
{
    private static ?bool $tableExists = null;

    /**
     * Active in-memory row buffer when inside a {@see buffer()} call. Each
     * write() appends here instead of inserting; the surrounding buffer()
     * flushes them in one shot.
     *
     * @var ?list<array<string, mixed>>
     */
    private static ?array $buffer = null;

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function write(
        string $event,
        ?string $subjectType = null,
        int|string|null $subjectId = null,
        array $payload = [],
        ?int $companyId = null,
        ?string $reason = null,
        ?string $summary = null,
    ): void {
        if (! self::tableExists()) {
            return;
        }

        $row = [
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => is_numeric($subjectId) ? (int) $subjectId : null,
            'company_id' => $companyId ?? (auth()->user()?->active_company_id ?? null),
            'causer_id' => auth()->id(),
            'reason' => $reason,
            'summary' => $summary !== null ? mb_substr($summary, 0, 500) : null,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            'created_at' => now(),
        ];

        if (self::$buffer !== null) {
            self::$buffer[] = $row;

            return;
        }

        DB::table('audit_logs')->insert($row);
    }

    /**
     * Run a callable with audit writes batched into a single bulk INSERT
     * at the end. Hot paths that touch many auditable models (POS
     * checkout, batch postings) collapse N round-trips to 1.
     *
     * Semantics:
     *   - Buffered rows flush once `$callback` returns successfully.
     *   - If `$callback` throws, the buffer is discarded — no audit
     *     trail of rolled-back work.
     *   - Nested buffer() calls degrade to a passthrough so the outer
     *     scope still owns the flush.
     *   - Callers should run their DB transaction *inside* this
     *     callable; the bulk insert then happens after commit, which
     *     keeps audit consistency intact.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function buffer(callable $callback): mixed
    {
        if (self::$buffer !== null) {
            // Already buffering — let the outer scope own the flush.
            return $callback();
        }

        self::$buffer = [];
        try {
            $result = $callback();
        } catch (\Throwable $e) {
            self::$buffer = null;
            throw $e;
        }

        $rows = self::$buffer;
        self::$buffer = null;

        if ($rows !== [] && self::tableExists()) {
            DB::table('audit_logs')->insert($rows);
        }

        return $result;
    }

    /**
     * Reset the cached table-existence check. Tests that create the
     * `audit_logs` table after the writer has already been touched should
     * call this in their setup so the next write isn't skipped.
     */
    public static function flushTableCheck(): void
    {
        self::$tableExists = null;
    }

    /**
     * True when the writer is currently inside a {@see buffer()} block.
     * Exposed for tests; production code should not branch on this.
     */
    public static function isBuffering(): bool
    {
        return self::$buffer !== null;
    }

    private static function tableExists(): bool
    {
        return self::$tableExists ??= Schema::hasTable('audit_logs');
    }
}
