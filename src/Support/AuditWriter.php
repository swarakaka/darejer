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
 *   payload       json          nullable
 *   ip            string(45)    nullable
 *   user_agent    string(255)   nullable
 *   created_at    timestamp
 */
final class AuditWriter
{
    private static ?bool $tableExists = null;

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
    ): void {
        if (! self::tableExists()) {
            return;
        }

        DB::table('audit_logs')->insert([
            'event' => $event,
            'subject_type' => $subjectType,
            'subject_id' => is_numeric($subjectId) ? (int) $subjectId : null,
            'company_id' => $companyId ?? (auth()->user()?->active_company_id ?? null),
            'causer_id' => auth()->id(),
            'reason' => $reason,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip' => request()?->ip(),
            'user_agent' => substr((string) request()?->userAgent(), 0, 255),
            'created_at' => now(),
        ]);
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

    private static function tableExists(): bool
    {
        return self::$tableExists ??= Schema::hasTable('audit_logs');
    }
}
