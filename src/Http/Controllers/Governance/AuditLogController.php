<?php

declare(strict_types=1);

namespace Darejer\Http\Controllers\Governance;

use Carbon\CarbonImmutable;
use Darejer\Http\Controllers\DarejerController;
use Darejer\Support\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only viewer over the host app's `audit_logs` table.
 *
 * Renders a dedicated Inertia page (`Governance/AuditLog`) that displays the
 * matching rows in a data table with row-click slideover for the full event
 * details (payload JSON, reason, IP, user agent). Filters keep the result
 * set bounded so a single request can never spill the whole event history.
 */
class AuditLogController extends DarejerController
{
    protected ?string $resource = 'audit-log';

    protected ?string $routeName = 'darejer.governance.audit-log';

    protected ?string $parameter = 'audit_log';

    protected ?string $routePrefix = 'darejer/governance';

    /** Hard cap on rows returned per request. */
    private const ROW_LIMIT = 500;

    public function index(Request $request): Response
    {
        $this->authorizePermission('audit.log.view');

        $companyId = (int) (auth()->user()?->active_company_id ?? 0);

        $event = (string) $request->input('event', '');
        $subjectType = (string) $request->input('subject_type', '');
        $subjectId = $request->input('subject_id');
        $causerId = $request->input('causer_id');
        $from = $request->filled('from')
            ? CarbonImmutable::parse((string) $request->input('from'))->startOfDay()
            : CarbonImmutable::now()->subDays(30)->startOfDay();
        $to = $request->filled('to')
            ? CarbonImmutable::parse((string) $request->input('to'))->endOfDay()
            : CarbonImmutable::now()->endOfDay();

        $rows = AuditLog::query()
            ->from('audit_logs as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.causer_id')
            ->select([
                'a.id', 'a.event', 'a.subject_type', 'a.subject_id',
                'a.causer_id', 'a.reason', 'a.summary', 'a.payload', 'a.ip', 'a.user_agent',
                'a.created_at', 'u.username as causer',
            ])
            ->where('a.company_id', $companyId)
            ->when($event !== '', fn ($q) => $q->where('a.event', 'like', $event.'%'))
            ->when($subjectType !== '', fn ($q) => $q->where('a.subject_type', $subjectType))
            ->when($subjectId, fn ($q) => $q->where('a.subject_id', $subjectId))
            ->when($causerId, fn ($q) => $q->where('a.causer_id', $causerId))
            ->whereBetween('a.created_at', [$from, $to])
            ->orderByDesc('a.id')
            ->limit(self::ROW_LIMIT)
            ->get();

        // Distinct event prefixes and subject types from the current company —
        // gives the filter dropdowns concrete options without a config file.
        $eventOptions = DB::table('audit_logs')
            ->where('company_id', $companyId)
            ->distinct()
            ->orderBy('event')
            ->pluck('event')
            ->all();

        $subjectTypeOptions = DB::table('audit_logs')
            ->where('company_id', $companyId)
            ->whereNotNull('subject_type')
            ->distinct()
            ->orderBy('subject_type')
            ->pluck('subject_type')
            ->all();

        Inertia::share('breadcrumbs', [
            ['label' => __darejer('Governance')],
            ['label' => __darejer('Audit Log')],
        ]);

        return Inertia::render('Governance/AuditLog', [
            'title' => __darejer('Audit Log'),
            'rows' => $rows->map(fn (AuditLog $r) => [
                'id' => (int) $r->id,
                'event' => $r->event,
                'subject_type' => $r->subject_type,
                'subject_id' => $r->subject_id,
                'causer_id' => $r->causer_id,
                'causer' => $r->getAttribute('causer'),
                'reason' => $r->reason,
                'summary' => $r->summary,
                'payload' => $r->payload,
                'ip' => $r->ip,
                'user_agent' => $r->user_agent,
                'created_at' => optional($r->created_at)->toIso8601String(),
            ])->all(),
            'total' => $rows->count(),
            'truncated' => $rows->count() === self::ROW_LIMIT,
            'rowLimit' => self::ROW_LIMIT,
            'filters' => [
                'event' => $event,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId !== null ? (string) $subjectId : '',
                'causer_id' => $causerId !== null ? (string) $causerId : '',
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'eventOptions' => $eventOptions,
            'subjectTypeOptions' => $subjectTypeOptions,
        ]);
    }

    private function authorizePermission(string $permission): void
    {
        $user = auth()->user();
        if (! $user || ! ($user->can($permission) || (method_exists($user, 'hasRole') && $user->hasRole('super-admin')))) {
            abort(403);
        }
    }
}
