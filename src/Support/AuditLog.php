<?php

declare(strict_types=1);

namespace Darejer\Support;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Read-only Eloquent model over the host's `audit_logs` table.
 *
 * The schema contract is documented in {@see AuditWriter}. Writes go through
 * `AuditWriter::write()` (which uses the query builder directly to avoid
 * model events recursing through the `Auditable` concern).
 *
 * @property int $id
 * @property string $event
 * @property string|null $subject_type
 * @property int|null $subject_id
 * @property int|null $company_id
 * @property int|null $causer_id
 * @property string|null $reason
 * @property array<string, mixed>|null $payload
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
final class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'payload' => AsArrayObject::class,
        'subject_id' => 'integer',
        'company_id' => 'integer',
        'causer_id' => 'integer',
        'created_at' => 'datetime',
    ];
}
