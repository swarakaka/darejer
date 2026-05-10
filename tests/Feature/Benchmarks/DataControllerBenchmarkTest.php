<?php

declare(strict_types=1);

use Darejer\Data\ModelRegistry;
use Darejer\Http\Controllers\DataController;
use Darejer\Tests\Support\Benchmark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class DataControllerBenchUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

class DataControllerBenchCity extends Model
{
    protected $table = 'dc_bench_cities';

    protected $guarded = [];

    public $timestamps = false;
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $b): void {
        $b->id();
        $b->string('name')->nullable();
    });
    Schema::create('dc_bench_cities', function (Blueprint $b): void {
        $b->id();
        $b->string('code')->index();
        $b->string('name')->index();
    });

    config()->set('auth.providers.users.model', DataControllerBenchUser::class);
    test()->actingAs(DataControllerBenchUser::query()->create(['name' => 'Bench']));

    $rows = [];
    for ($i = 0; $i < 1_000; $i++) {
        $rows[] = ['code' => sprintf('CITY-%05d', $i), 'name' => "City $i"];
    }
    foreach (array_chunk($rows, 200) as $chunk) {
        \DB::table('dc_bench_cities')->insert($chunk);
    }

    ModelRegistry::flush();
    ModelRegistry::register(['city' => DataControllerBenchCity::class]);
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('dc_bench_cities');
    Schema::dropIfExists('users');
});

function dcRequest(array $params = []): Request
{
    $request = Request::create('/darejer/data/city', 'GET', $params);
    $request->setUserResolver(fn () => auth()->user());

    return $request;
}

it('benchmarks DataController::index — combobox autocomplete (1000 rows)', function () {
    $request = dcRequest(['combobox' => 1, 'q' => 'City 5', 'per_page' => 20]);

    $result = Benchmark::run('DataController::index (combobox, q=City 5)', fn () => (new DataController)->index($request, 'city'))
        ->report();

    // 1 count + 1 page = floor; rate-limiter check is in-memory.
    $result->assertQueriesAtMost(5)->assertFasterThan(300);
});

it('benchmarks DataController::index — full page (1000 rows)', function () {
    $request = dcRequest(['per_page' => 50]);

    $result = Benchmark::run('DataController::index (per_page=50)', fn () => (new DataController)->index($request, 'city'))
        ->report();

    $result->assertQueriesAtMost(5)->assertFasterThan(300);
});

it('benchmarks DataController::index — tree mode', function () {
    // Tree mode pulls every row, transforms, then nests in PHP. Worth
    // measuring because 1k rows in memory is not free.
    $request = dcRequest(['tree' => 1]);

    $result = Benchmark::run('DataController::index (tree, all rows)', fn () => (new DataController)->index($request, 'city'))
        ->report();

    $result->assertQueriesAtMost(4)->assertFasterThan(800);
});
