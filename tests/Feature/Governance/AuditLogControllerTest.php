<?php

declare(strict_types=1);

use Darejer\Http\Controllers\Governance\AuditLogController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Inertia\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuditLogTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('username')->nullable();
        $table->string('email')->nullable();
        $table->unsignedBigInteger('active_company_id')->nullable();
    });

    Schema::create('audit_logs', function (Blueprint $table): void {
        $table->id();
        $table->string('event', 64);
        $table->string('subject_type', 191)->nullable();
        $table->unsignedBigInteger('subject_id')->nullable();
        $table->unsignedBigInteger('company_id')->nullable();
        $table->unsignedBigInteger('causer_id')->nullable();
        $table->text('reason')->nullable();
        $table->string('summary', 500)->nullable();
        $table->json('payload')->nullable();
        $table->string('ip', 45)->nullable();
        $table->string('user_agent', 255)->nullable();
        $table->timestamp('created_at')->useCurrent();
    });

    config()->set('auth.providers.users.model', AuditLogTestUser::class);

    // Bypass Spatie permission lookups: the controller only needs `can()` to
    // return true for the audit viewer permission.
    Gate::before(fn () => true);

    $user = AuditLogTestUser::query()->create([
        'username' => 'Auditor',
        'email' => 'auditor@example.test',
        'active_company_id' => 1,
    ]);

    test()->actingAs($user);
});

afterEach(function (): void {
    Schema::dropIfExists('audit_logs');
    Schema::dropIfExists('users');
});

function inertiaPage(Response $response, Request $request): array
{
    $request->headers->set('X-Inertia', 'true');

    return $response->toResponse($request)->getData(true);
}

function seedAuditRow(array $overrides = []): int
{
    return DB::table('audit_logs')->insertGetId(array_merge([
        'event' => 'document.created',
        'subject_type' => 'App\\Domain\\Accounting\\Models\\Document',
        'subject_id' => 100,
        'company_id' => 1,
        'causer_id' => null,
        'reason' => 'opening balance',
        'payload' => json_encode(['attributes' => ['code' => 'DOC-1']]),
        'ip' => '127.0.0.1',
        'user_agent' => 'phpunit',
        'created_at' => now(),
    ], $overrides));
}

it('renders the Governance/AuditLog page with rows scoped to the active company', function (): void {
    $mineA = seedAuditRow(['event' => 'document.posted']);
    $mineB = seedAuditRow(['event' => 'document.reversed']);
    seedAuditRow(['company_id' => 999]); // different company — must not appear

    $request = Request::create('/darejer/governance/audit-log', 'GET');

    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect($page['component'])->toBe('Governance/AuditLog');

    $rows = $page['props']['rows'];
    expect($rows)->toHaveCount(2);
    expect(collect($rows)->pluck('id')->all())->toEqualCanonicalizing([$mineA, $mineB]);
    expect($page['props']['total'])->toBe(2);
    expect($page['props']['truncated'])->toBeFalse();
});

it('filters rows by event prefix', function (): void {
    seedAuditRow(['event' => 'document.posted']);
    seedAuditRow(['event' => 'document.reversed']);
    seedAuditRow(['event' => 'lead.qualified']);

    $request = Request::create('/darejer/governance/audit-log', 'GET', ['event' => 'document']);

    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect($page['props']['rows'])->toHaveCount(2);
    expect(collect($page['props']['rows'])->pluck('event')->all())
        ->toEqualCanonicalizing(['document.posted', 'document.reversed']);
});

it('filters rows by subject_type, subject_id, and causer_id', function (): void {
    seedAuditRow(['subject_type' => 'A\\B', 'subject_id' => 1, 'causer_id' => 7]);
    seedAuditRow(['subject_type' => 'A\\B', 'subject_id' => 2, 'causer_id' => 7]);
    seedAuditRow(['subject_type' => 'X\\Y', 'subject_id' => 1, 'causer_id' => 7]);
    seedAuditRow(['subject_type' => 'A\\B', 'subject_id' => 1, 'causer_id' => 9]);

    $request = Request::create('/darejer/governance/audit-log', 'GET', [
        'subject_type' => 'A\\B',
        'subject_id' => 1,
        'causer_id' => 7,
    ]);

    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect($page['props']['rows'])->toHaveCount(1);
    expect($page['props']['rows'][0]['subject_type'])->toBe('A\\B');
});

it('filters by date range using inclusive endpoints', function (): void {
    seedAuditRow(['event' => 'old', 'created_at' => '2026-04-01 10:00:00']);
    seedAuditRow(['event' => 'inrange', 'created_at' => '2026-04-15 23:00:00']);
    seedAuditRow(['event' => 'future', 'created_at' => '2026-04-30 10:00:00']);

    $request = Request::create('/darejer/governance/audit-log', 'GET', [
        'from' => '2026-04-10',
        'to' => '2026-04-20',
    ]);

    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect(collect($page['props']['rows'])->pluck('event')->all())->toBe(['inrange']);
});

it('exposes distinct event and subject type options for filter dropdowns', function (): void {
    seedAuditRow(['event' => 'document.created', 'subject_type' => 'A\\B']);
    seedAuditRow(['event' => 'document.posted', 'subject_type' => 'A\\B']);
    seedAuditRow(['event' => 'lead.qualified', 'subject_type' => 'L\\Lead']);

    $request = Request::create('/darejer/governance/audit-log', 'GET');

    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect($page['props']['eventOptions'])
        ->toEqualCanonicalizing(['document.created', 'document.posted', 'lead.qualified']);
    expect($page['props']['subjectTypeOptions'])
        ->toEqualCanonicalizing(['A\\B', 'L\\Lead']);
});

it('joins users to expose the causer username on each row', function (): void {
    $causer = AuditLogTestUser::query()->create([
        'username' => 'jane.doe',
        'email' => 'jane@example.test',
        'active_company_id' => 1,
    ]);
    seedAuditRow(['causer_id' => $causer->id]);
    seedAuditRow(['causer_id' => null]);

    $request = Request::create('/darejer/governance/audit-log', 'GET');
    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    $byCauser = collect($page['props']['rows'])->keyBy('causer_id');
    expect($byCauser[$causer->id]['causer'])->toBe('jane.doe');
    expect($byCauser['']['causer'] ?? null)->toBeNull();
});

it('exposes the human summary in the row props', function (): void {
    seedAuditRow(['summary' => "Created Document 'DOC-1'"]);

    $request = Request::create('/darejer/governance/audit-log', 'GET');
    $response = (new AuditLogController)->index($request);
    $page = inertiaPage($response, $request);

    expect($page['props']['rows'][0]['summary'])->toBe("Created Document 'DOC-1'");
});

it('aborts with 403 when the user lacks audit.log.view permission', function (): void {
    // Re-authenticate as a user model whose `can()` always returns false,
    // bypassing the permissive Gate::before set in beforeEach.
    test()->actingAs(new class extends Authenticatable
    {
        public function can($abilities, $arguments = []): bool
        {
            return false;
        }
    });

    $request = Request::create('/darejer/governance/audit-log', 'GET');

    expect(fn () => (new AuditLogController)->index($request))
        ->toThrow(HttpException::class);
});
