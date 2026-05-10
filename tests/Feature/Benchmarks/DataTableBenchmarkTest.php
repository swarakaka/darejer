<?php

declare(strict_types=1);

use Darejer\DataGrid\Column;
use Darejer\DataGrid\Filter;
use Darejer\DataTable\DataTable;
use Darejer\Tests\Support\Benchmark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DataTableBenchUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

class DataTableBenchProduct extends Model
{
    protected $table = 'datatable_bench_products';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
    });
    Schema::create('datatable_bench_products', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->index();
        $table->string('name');
        $table->string('status')->default('active')->index();
        $table->decimal('price', 12, 2)->default(0);
        $table->boolean('in_stock')->default(true);
        $table->date('released_on')->nullable();
    });

    config()->set('auth.providers.users.model', DataTableBenchUser::class);

    test()->actingAs(DataTableBenchUser::query()->create(['name' => 'Bench']));

    // 500 rows so pagination, sorting, and toArray() all do real work.
    $rows = [];
    $now = now();
    for ($i = 0; $i < 500; $i++) {
        $rows[] = [
            'code' => sprintf('P-%05d', $i),
            'name' => "Product $i",
            'status' => $i % 5 === 0 ? 'archived' : 'active',
            'price' => 10 + ($i % 100),
            'in_stock' => $i % 3 !== 0,
            'released_on' => $now->copy()->subDays($i % 365)->toDateString(),
        ];
    }
    foreach (array_chunk($rows, 200) as $chunk) {
        \DB::table('datatable_bench_products')->insert($chunk);
    }
});

afterEach(function (): void {
    Schema::dropIfExists('datatable_bench_products');
    Schema::dropIfExists('users');
});

function benchTable(): DataTable
{
    return DataTable::make(DataTableBenchProduct::class)
        ->columns([
            Column::make('code')->label('Code')->sortable()->searchable(),
            Column::make('name')->label('Name')->searchable(),
            Column::make('status')->label('Status')->sortable(),
            Column::make('price')->label('Price')->money(2)->alignRight(),
            Column::make('in_stock')->label('In Stock')->boolean(),
            Column::make('released_on')->label('Released')->date(),
        ])
        ->filters([
            Filter::text('code')->label('Code'),
            Filter::select('status')->label('Status')->options([
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'archived', 'label' => 'Archived'],
            ]),
            Filter::dateRange('released_on')->label('Released'),
        ]);
}

it('benchmarks DataTable render — 500 rows, default page', function () {
    $request = Request::create('/x', 'GET');
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (500 rows, default)', fn () => benchTable()->render($request))
        ->report();

    // 1 count + 1 page query is the floor; allow auth + driver chatter.
    $result->assertQueriesAtMost(6)->assertFasterThan(300);
});

it('benchmarks DataTable render with text search', function () {
    $request = Request::create('/x', 'GET', ['search' => 'Product 1']);
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (search=Product 1)', fn () => benchTable()->render($request))
        ->report();

    $result->assertQueriesAtMost(6)->assertFasterThan(300);
});

it('benchmarks DataTable render with select filter', function () {
    $request = Request::create('/x', 'GET', ['status' => 'archived']);
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (status=archived)', fn () => benchTable()->render($request))
        ->report();

    $result->assertQueriesAtMost(6)->assertFasterThan(300);
});

it('benchmarks DataTable render with date-range filter', function () {
    $request = Request::create('/x', 'GET', [
        'released_on' => ['from' => now()->subDays(60)->toDateString(), 'to' => now()->toDateString()],
    ]);
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (date range)', fn () => benchTable()->render($request))
        ->report();

    $result->assertQueriesAtMost(6)->assertFasterThan(300);
});

it('benchmarks DataTable render sorted desc by price', function () {
    $request = Request::create('/x', 'GET', ['sort' => 'price', 'order' => 'desc']);
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (sort=-price)', fn () => benchTable()->render($request))
        ->report();

    $result->assertQueriesAtMost(6)->assertFasterThan(300);
});

it('benchmarks DataTable render with a higher per_page', function () {
    $request = Request::create('/x', 'GET', ['per_page' => 100]);
    $request->setUserResolver(fn () => auth()->user());

    $result = Benchmark::run('DataTable::render (per_page=100)', fn () => benchTable()->render($request))
        ->report();

    $result->assertQueriesAtMost(6)->assertFasterThan(500);
});
