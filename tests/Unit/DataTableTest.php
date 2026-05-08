<?php

declare(strict_types=1);

use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataTable\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DataTableTestModel extends Model
{
    //
}

class DataTableDateRangeModel extends Model
{
    protected $table = 'data_table_date_range';

    protected $guarded = [];

    public $timestamps = false;
}

function callBuildRenderColumns(DataTable $table): array
{
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildRenderColumns');
    $method->setAccessible(true);

    return $method->invoke($table);
}

function callBuildQuery(DataTable $table, Request $request): Builder
{
    $reflection = new ReflectionClass($table);
    $method = $reflection->getMethod('buildQuery');
    $method->setAccessible(true);

    return $method->invoke($table, $request);
}

it('hides the id column by default but keeps it in the column list', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([
            Column::make('id')->label('#'),
            Column::make('code')->label('Code'),
        ]);

    $columns = callBuildRenderColumns($table);
    $serialized = array_map(fn (Column $c) => $c->toArray(), $columns);

    expect($serialized)->toHaveCount(2);
    expect($serialized[0]['field'])->toBe('id');
    expect($serialized[0]['hidden'] ?? false)->toBeTrue();
    expect($serialized[1]['field'])->toBe('code');
    expect($serialized[1]['hidden'] ?? false)->toBeFalse();
});

it('does not add a numeric column unless numeric() is called', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([Column::make('code')->label('Code')]);

    $fields = array_map(fn (Column $c) => $c->getField(), callBuildRenderColumns($table));

    expect($fields)->toBe(['code']);
});

it('prepends a # row-number column when numeric() is enabled', function () {
    $table = DataTable::make(DataTableTestModel::class)
        ->columns([Column::make('code')->label('Code')])
        ->numeric();

    $columns = callBuildRenderColumns($table);
    $serialized = array_map(fn (Column $c) => $c->toArray(), $columns);

    expect($columns)->toHaveCount(2);
    expect($columns[0]->getField())->toBe('__row_number');
    expect($serialized[0]['label'])->toBe('#');
    expect($serialized[0]['align'] ?? null)->toBe('right');
    expect($columns[1]->getField())->toBe('code');
});

it('numeric() returns the same DataTable instance for chaining', function () {
    $table = DataTable::make(DataTableTestModel::class);

    expect($table->numeric())->toBe($table);
    expect($table->numeric(false))->toBe($table);
});

describe('Filter::dateRange', function () {
    beforeEach(function (): void {
        Schema::create('data_table_date_range', function (Blueprint $table): void {
            $table->id();
            $table->date('statement_date');
        });

        DataTableDateRangeModel::query()->create(['statement_date' => '2026-01-15']);
        DataTableDateRangeModel::query()->create(['statement_date' => '2026-03-10']);
        DataTableDateRangeModel::query()->create(['statement_date' => '2026-06-20']);
        DataTableDateRangeModel::query()->create(['statement_date' => '2026-09-05']);
    });

    it('emits a "daterange" filter type', function () {
        expect(Filter::dateRange('statement_date')->toArray())
            ->toMatchArray(['field' => 'statement_date', 'type' => 'daterange']);
    });

    it('filters rows when both from and to are supplied', function () {
        $table = DataTable::make(DataTableDateRangeModel::class)
            ->columns([Column::make('statement_date')])
            ->filters([Filter::dateRange('statement_date')]);

        $request = Request::create('/', 'GET', [
            'statement_date' => ['from' => '2026-03-01', 'to' => '2026-06-30'],
        ]);

        $dates = callBuildQuery($table, $request)->pluck('statement_date')
            ->map(fn ($d) => (string) $d)->all();

        expect($dates)->toBe(['2026-03-10', '2026-06-20']);
    });

    it('filters using only the "from" bound when "to" is empty', function () {
        $table = DataTable::make(DataTableDateRangeModel::class)
            ->columns([Column::make('statement_date')])
            ->filters([Filter::dateRange('statement_date')]);

        $request = Request::create('/', 'GET', [
            'statement_date' => ['from' => '2026-06-01'],
        ]);

        $dates = callBuildQuery($table, $request)->pluck('statement_date')
            ->map(fn ($d) => (string) $d)->all();

        expect($dates)->toBe(['2026-06-20', '2026-09-05']);
    });

    it('filters using only the "to" bound when "from" is empty', function () {
        $table = DataTable::make(DataTableDateRangeModel::class)
            ->columns([Column::make('statement_date')])
            ->filters([Filter::dateRange('statement_date')]);

        $request = Request::create('/', 'GET', [
            'statement_date' => ['to' => '2026-03-31'],
        ]);

        $dates = callBuildQuery($table, $request)->pluck('statement_date')
            ->map(fn ($d) => (string) $d)->all();

        expect($dates)->toBe(['2026-01-15', '2026-03-10']);
    });
});
