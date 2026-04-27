<?php

declare(strict_types=1);

use Darejer\Concerns\Auditable;
use Darejer\Support\AuditWriter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * In-memory test model. Defined at the top level so PHP autoload doesn't
 * try to resolve it from a file.
 */
class AuditableTestWidget extends Model
{
    use Auditable;

    protected $table = 'auditable_test_widgets';

    protected $guarded = [];

    public $timestamps = true;
}

beforeEach(function (): void {
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

    Schema::create('auditable_test_widgets', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('company_id')->nullable();
        $table->string('code')->nullable();
        $table->string('name')->nullable();
        $table->string('password')->nullable();
        $table->json('settings')->nullable();
        $table->timestamps();
    });

    AuditWriter::flushTableCheck();
});

afterEach(function (): void {
    Schema::dropIfExists('auditable_test_widgets');
    Schema::dropIfExists('audit_logs');
});

it('records a created event with attributes payload', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    $row = DB::table('audit_logs')->first();

    expect($row)->not->toBeNull()
        ->and($row->event)->toBe('model.auditable_test_widgets.created')
        ->and($row->subject_type)->toBe(AuditableTestWidget::class)
        ->and((int) $row->subject_id)->toBe($widget->id)
        ->and((int) $row->company_id)->toBe(7);

    $payload = json_decode($row->payload, true);
    expect($payload)->toHaveKey('attributes')
        ->and($payload['attributes']['code'])->toBe('W-001')
        ->and($payload['attributes'])->not->toHaveKey('created_at')
        ->and($payload['attributes'])->not->toHaveKey('updated_at');
});

it('records an updated event with old/new diff and ignores unchanged columns', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Old',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->name = 'New';
    $widget->save();

    $row = DB::table('audit_logs')->first();
    expect($row->event)->toBe('model.auditable_test_widgets.updated');

    $payload = json_decode($row->payload, true);
    expect($payload['changes'])
        ->toHaveCount(1)
        ->toHaveKey('name')
        ->and($payload['changes']['name'])->toMatchArray([
            'old' => 'Old',
            'new' => 'New',
        ]);
});

it('skips an updated event when only timestamps changed', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->touch();

    expect(DB::table('audit_logs')->count())->toBe(0);
});

it('records a deleted event with key and identity hint', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->delete();

    $row = DB::table('audit_logs')->first();
    expect($row->event)->toBe('model.auditable_test_widgets.deleted');

    $payload = json_decode($row->payload, true);
    expect($payload['key'])->toBe($widget->id)
        ->and($payload['identity'])->toMatchArray([
            'code' => 'W-001',
            'name' => 'Hello',
        ]);
});

it('redacts password and other sensitive attributes from the payload', function (): void {
    AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
        'password' => 'super-secret',
    ]);

    $row = DB::table('audit_logs')->first();
    $payload = json_decode($row->payload, true);

    expect($payload['attributes'])->not->toHaveKey('password');
});

it('suppresses auditing inside withoutAuditing', function (): void {
    AuditableTestWidget::withoutAuditing(function (): void {
        AuditableTestWidget::query()->create([
            'company_id' => 7,
            'code' => 'W-001',
            'name' => 'Hello',
        ]);
    });

    expect(DB::table('audit_logs')->count())->toBe(0);
});

it('still audits after withoutAuditing returns', function (): void {
    AuditableTestWidget::withoutAuditing(fn () => AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'A',
    ]));

    AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-002',
        'name' => 'B',
    ]);

    expect(DB::table('audit_logs')->count())->toBe(1);
});

it('records a human-readable summary on create using the model label', function (): void {
    AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    $row = DB::table('audit_logs')->first();

    expect($row->summary)->toBe("Created Auditable Test Widget 'W-001'");
});

it('records a human-readable summary on single-field update with old/new values', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Old',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->name = 'New';
    $widget->save();

    $row = DB::table('audit_logs')->first();

    expect($row->summary)->toBe("Changed name from 'Old' to 'New' on Auditable Test Widget 'W-001'");
});

it('collapses multi-field updates into a count summary', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Old',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->code = 'W-002';
    $widget->name = 'New';
    $widget->save();

    $row = DB::table('audit_logs')->first();

    expect($row->summary)->toBe("Updated Auditable Test Widget 'W-002' (2 changes)");
});

it('records a human-readable summary on delete', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    DB::table('audit_logs')->truncate();

    $widget->delete();

    $row = DB::table('audit_logs')->first();

    expect($row->summary)->toBe("Deleted Auditable Test Widget 'W-001'");
});

it('falls back to "#id" in the summary when the model has no recognizable label', function (): void {
    $widget = AuditableTestWidget::query()->create([
        'company_id' => 7,
    ]);

    $row = DB::table('audit_logs')->first();

    expect($row->summary)->toBe("Created Auditable Test Widget #{$widget->id}");
});

it('writes nothing when the audit_logs table does not exist', function (): void {
    Schema::dropIfExists('audit_logs');
    AuditWriter::flushTableCheck();

    AuditableTestWidget::query()->create([
        'company_id' => 7,
        'code' => 'W-001',
        'name' => 'Hello',
    ]);

    expect(Schema::hasTable('audit_logs'))->toBeFalse();
});
