<?php

declare(strict_types=1);

use Darejer\Actions\BulkAction;
use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataGrid\RowAction;
use Darejer\DataTable\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class TrashedFilterModel extends Model
{
    use SoftDeletes;

    protected $table = 'trashed_filter_items';

    protected $guarded = [];

    public $timestamps = false;
}

class TrashedFilterPlainModel extends Model
{
    protected $table = 'trashed_filter_plain';

    protected $guarded = [];

    public $timestamps = false;
}

function callTrashedBuildQuery(DataTable $table, Request $request): Builder
{
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildQuery');
    $method->setAccessible(true);

    return $method->invoke($table, $request);
}

beforeEach(function (): void {
    Schema::create('trashed_filter_items', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->softDeletes();
    });

    Schema::create('trashed_filter_plain', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
    });

    TrashedFilterModel::query()->create(['code' => 'A']);
    TrashedFilterModel::query()->create(['code' => 'B']);
    $deleted = TrashedFilterModel::query()->create(['code' => 'C']);
    $deleted->delete();

    TrashedFilterPlainModel::query()->create(['code' => 'X']);
    TrashedFilterPlainModel::query()->create(['code' => 'Y']);
});

describe('Filter::trashed', function () {
    it('emits a "trashed" filter type with default field "trashed"', function () {
        expect(Filter::trashed()->toArray())
            ->toMatchArray(['field' => 'trashed', 'type' => 'trashed']);
    });

    it('accepts a custom field name', function () {
        expect(Filter::trashed('show_deleted')->toArray()['field'])
            ->toBe('show_deleted');
    });
});

describe('DataTable trashed scope', function () {
    it('hides soft-deleted rows by default', function () {
        $table = DataTable::make(TrashedFilterModel::class)
            ->columns([Column::make('code')])
            ->filters([Filter::trashed()]);

        $codes = callTrashedBuildQuery($table, Request::create('/', 'GET'))
            ->pluck('code')->all();

        expect($codes)->toEqualCanonicalizing(['A', 'B']);
    });

    it('returns all rows when trashed=with', function () {
        $table = DataTable::make(TrashedFilterModel::class)
            ->columns([Column::make('code')])
            ->filters([Filter::trashed()]);

        $codes = callTrashedBuildQuery($table, Request::create('/', 'GET', ['trashed' => 'with']))
            ->pluck('code')->all();

        expect($codes)->toEqualCanonicalizing(['A', 'B', 'C']);
    });

    it('returns only trashed rows when trashed=only', function () {
        $table = DataTable::make(TrashedFilterModel::class)
            ->columns([Column::make('code')])
            ->filters([Filter::trashed()]);

        $codes = callTrashedBuildQuery($table, Request::create('/', 'GET', ['trashed' => 'only']))
            ->pluck('code')->all();

        expect($codes)->toBe(['C']);
    });

    it('silently ignores the trashed filter on models without SoftDeletes', function () {
        $table = DataTable::make(TrashedFilterPlainModel::class)
            ->columns([Column::make('code')])
            ->filters([Filter::trashed()]);

        $codes = callTrashedBuildQuery($table, Request::create('/', 'GET', ['trashed' => 'only']))
            ->pluck('code')->all();

        // Filter is a no-op; both plain rows are returned.
        expect($codes)->toEqualCanonicalizing(['X', 'Y']);
    });

    it('does not treat the trashed filter as a column predicate', function () {
        // If the filter weren't intercepted, the default branch would emit
        // `where trashed like '%only%'` and SQL would explode (no such column).
        $table = DataTable::make(TrashedFilterModel::class)
            ->columns([Column::make('code')])
            ->filters([Filter::trashed()]);

        $sql = callTrashedBuildQuery($table, Request::create('/', 'GET', ['trashed' => 'only']))
            ->toSql();

        expect($sql)->not->toContain('"trashed"');
        expect($sql)->not->toContain('`trashed`');
    });
});

describe('RowAction soft-delete factories', function () {
    it('restore() requires a deleted_at value via dependOn', function () {
        $serialized = RowAction::restore('/items/{id}/restore')->toArray();

        expect($serialized['type'])->toBe('restore')
            ->and($serialized['method'])->toBe('PATCH')
            ->and($serialized['dependOn'])->toMatchArray([
                'field' => 'deleted_at',
                'operator' => 'notEmpty',
            ]);
    });

    it('forceDelete() requires a deleted_at value via dependOn', function () {
        $serialized = RowAction::forceDelete('/items/{id}/force')->toArray();

        expect($serialized['type'])->toBe('forceDelete')
            ->and($serialized['method'])->toBe('DELETE')
            ->and($serialized['variant'])->toBe('destructive')
            ->and($serialized['dependOn'])->toMatchArray([
                'field' => 'deleted_at',
                'operator' => 'notEmpty',
            ]);
    });

    it('delete() hides itself on already-trashed rows', function () {
        $serialized = RowAction::delete('/items/{id}')->toArray();

        // Note: dependOn is array_filtered, so a literal `null` value is
        // dropped — verify the field/operator are present and equality-based.
        expect($serialized['dependOn']['field'])->toBe('deleted_at')
            ->and($serialized['dependOn']['operator'])->toBe('eq');
    });
});

describe('BulkAction soft-delete factories', function () {
    it('delete() emits a destructive DELETE BulkAction', function () {
        $serialized = BulkAction::delete('/items/bulk-destroy')->toArray();

        expect($serialized)->toMatchArray([
            'type' => 'BulkAction',
            'method' => 'DELETE',
            'variant' => 'destructive',
            'batchUrl' => '/items/bulk-destroy',
        ]);
    });

    it('restore() emits a PATCH BulkAction gated on the trashed filter', function () {
        $serialized = BulkAction::restore('/items/bulk-restore')->toArray();

        expect($serialized['method'])->toBe('PATCH')
            ->and($serialized['batchUrl'])->toBe('/items/bulk-restore')
            ->and($serialized['dependOn'])->toMatchArray([
                'field' => 'trashed',
                'operator' => 'in',
                'value' => ['with', 'only'],
            ]);
    });

    it('forceDelete() emits a destructive DELETE BulkAction gated on "only trashed"', function () {
        $serialized = BulkAction::forceDelete('/items/bulk-force')->toArray();

        expect($serialized)->toMatchArray([
            'method' => 'DELETE',
            'variant' => 'destructive',
            'batchUrl' => '/items/bulk-force',
        ]);
        expect($serialized['dependOn'])->toMatchArray([
            'field' => 'trashed',
            'operator' => 'in',
            'value' => ['only'],
        ]);
    });
});
