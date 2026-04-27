<?php

declare(strict_types=1);

namespace Darejer\Concerns;

use Darejer\Support\AuditWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Mix into any Eloquent model to record `created`, `updated` and `deleted`
 * events to the host app's `audit_logs` table.
 *
 * Event names follow `model.<table>.<event>`:
 *
 *   model.leads.created
 *   model.leads.updated
 *   model.leads.deleted
 *
 * This is the *automatic* data-change stream. Domain-meaningful events
 * (e.g. `crm.lead.qualified`) are still recorded explicitly by Actions
 * via the host app's own logger and live under their own dotted prefix.
 *
 * Suppress in seeders / bulk imports:
 *
 *   Lead::withoutAuditing(fn () => Lead::factory()->count(1000)->create());
 *
 * @mixin Model
 */
trait Auditable
{
    /**
     * Process-wide kill switch. Toggled by {@see withoutAuditing()}.
     */
    private static bool $auditingDisabled = false;

    public static function bootAuditable(): void
    {
        static::created(static function (Model $model): void {
            self::recordAuditEvent($model, 'created');
        });

        static::updated(static function (Model $model): void {
            self::recordAuditEvent($model, 'updated');
        });

        static::deleted(static function (Model $model): void {
            self::recordAuditEvent($model, 'deleted');
        });
    }

    /**
     * Run a callback with auditing suppressed for ALL Auditable models.
     *
     * @template T
     *
     * @param  callable():T  $callback
     * @return T
     */
    public static function withoutAuditing(callable $callback): mixed
    {
        $previous = self::$auditingDisabled;
        self::$auditingDisabled = true;

        try {
            return $callback();
        } finally {
            self::$auditingDisabled = $previous;
        }
    }

    /**
     * Override on the model to skip auditing per-instance (e.g. system rows).
     */
    public function shouldAudit(): bool
    {
        return true;
    }

    /**
     * Attribute keys redacted from the audit payload. Override to extend.
     *
     * @return list<string>
     */
    public function auditExcluded(): array
    {
        return [
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'api_token',
            'created_at',
            'updated_at',
            'deleted_at',
        ];
    }

    /**
     * Build the payload for an audit event. Override on the model for a
     * custom shape; the default already strips sensitive attributes.
     *
     * @return array<string, mixed>
     */
    public function auditPayload(string $event): array
    {
        $excluded = array_flip($this->auditExcluded());

        return match ($event) {
            'created' => [
                'attributes' => array_diff_key($this->getAttributes(), $excluded),
            ],
            'updated' => [
                'changes' => $this->buildUpdatedDiff($excluded),
            ],
            'deleted' => [
                'key' => $this->getKey(),
                'identity' => $this->buildDeletedIdentity(),
            ],
            default => [],
        };
    }

    /**
     * Plain-language sentence describing this event for non-developers, e.g.
     * "Created Lead 'Acme Corp'" or "Changed status from 'New' to 'Qualified'
     * on Lead 'Acme Corp'". Override on the model for full control.
     */
    public function auditSummary(string $event): ?string
    {
        $type = $this->auditTypeLabel();
        $label = $this->auditLabel();
        $subject = $label !== null ? "{$type} '{$label}'" : "{$type} #{$this->getKey()}";

        return match ($event) {
            'created' => "Created {$subject}",
            'deleted' => "Deleted {$subject}",
            'updated' => $this->buildUpdatedSummary($subject),
            default => null,
        };
    }

    /**
     * Display name for the model's class — used as the "type" in summaries.
     * Defaults to the humanized class basename ("Lead", "Document", "Bank
     * account"). Override to translate or rename.
     */
    public function auditTypeLabel(): string
    {
        return Str::headline(class_basename(static::class));
    }

    /**
     * Short identifier for this row that a user would recognize, e.g. the
     * code, number, name or title. Returning null falls back to "#<id>".
     */
    public function auditLabel(): ?string
    {
        $attributes = $this->getAttributes();

        foreach (['code', 'number', 'name', 'title'] as $key) {
            if (! empty($attributes[$key])) {
                return (string) $attributes[$key];
            }
        }

        return null;
    }

    /**
     * Display name for an attribute key — used when rendering a single-field
     * change ("Changed pipeline stage from..."). Strips trailing `_id` and
     * humanizes snake_case. Override to translate or rename.
     */
    public function auditFieldLabel(string $key): string
    {
        $key = preg_replace('/_id$/', '', $key) ?? $key;

        return Str::lower(Str::headline($key));
    }

    private function buildUpdatedSummary(string $subject): string
    {
        $excluded = array_flip($this->auditExcluded());
        $changes = array_diff_key($this->getDirty(), $excluded);
        $count = count($changes);

        if ($count === 0) {
            return "Updated {$subject}";
        }

        if ($count === 1) {
            $key = (string) array_key_first($changes);
            $field = $this->auditFieldLabel($key);
            $old = $this->formatAuditValue($this->getRawOriginal($key));
            $new = $this->formatAuditValue($changes[$key]);

            return "Changed {$field} from {$old} to {$new} on {$subject}";
        }

        return "Updated {$subject} ({$count} changes)";
    }

    private function formatAuditValue(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '(empty)';
        }

        if (is_bool($value)) {
            return $value ? 'yes' : 'no';
        }

        if (is_scalar($value)) {
            return "'".(string) $value."'";
        }

        return "'".json_encode($value, JSON_UNESCAPED_UNICODE)."'";
    }

    /**
     * @param  array<string, int>  $excluded
     * @return array<string, array{old: mixed, new: mixed}>
     */
    private function buildUpdatedDiff(array $excluded): array
    {
        $diff = [];

        foreach ($this->getDirty() as $key => $new) {
            if (isset($excluded[$key])) {
                continue;
            }

            $diff[$key] = [
                'old' => $this->getRawOriginal($key),
                'new' => $new,
            ];
        }

        return $diff;
    }

    /**
     * Tiny human-readable hint stored alongside a delete event, when the
     * model has any of these well-known identifying columns.
     *
     * @return array<string, mixed>
     */
    private function buildDeletedIdentity(): array
    {
        $identity = [];
        $attributes = $this->getAttributes();

        foreach (['code', 'number', 'name', 'title'] as $key) {
            if (array_key_exists($key, $attributes)) {
                $identity[$key] = $this->getRawOriginal($key) ?? $attributes[$key];
            }
        }

        return $identity;
    }

    private static function recordAuditEvent(Model $model, string $event): void
    {
        if (self::$auditingDisabled) {
            return;
        }

        if (! $model->shouldAudit()) {
            return;
        }

        // Skip "updated" events where only excluded keys (timestamps, tokens)
        // changed — Eloquent fires `updated` even when only timestamps refresh
        // via touch().
        if ($event === 'updated') {
            $excluded = array_flip($model->auditExcluded());
            $relevant = array_diff_key($model->getDirty(), $excluded);

            if ($relevant === []) {
                return;
            }
        }

        $companyId = null;
        if (array_key_exists('company_id', $model->getAttributes())) {
            $value = $model->getAttribute('company_id');
            $companyId = $value === null ? null : (int) $value;
        }

        AuditWriter::write(
            event: sprintf('model.%s.%s', $model->getTable(), $event),
            subjectType: $model::class,
            subjectId: $model->getKey(),
            payload: $model->auditPayload($event),
            companyId: $companyId,
            summary: $model->auditSummary($event),
        );
    }
}
