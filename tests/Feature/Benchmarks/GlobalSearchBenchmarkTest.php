<?php

declare(strict_types=1);

use Darejer\Concerns\Searchable;
use Darejer\Data\ModelRegistry;
use Darejer\Support\GlobalSearch;
use Darejer\Tests\Support\Benchmark;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class GlobalSearchBenchProduct extends Model
{
    use Searchable;

    protected $table = 'gs_bench_products';

    protected $guarded = [];

    public $timestamps = false;

    protected array $searchable = ['code', 'name'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';

    protected ?string $searchableTypeLabel = 'Product';
}

class GlobalSearchBenchCustomer extends Model
{
    use Searchable;

    protected $table = 'gs_bench_customers';

    protected $guarded = [];

    public $timestamps = false;

    protected array $searchable = ['code', 'name', 'email'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';

    protected ?string $searchableTypeLabel = 'Customer';
}

class GlobalSearchBenchSupplier extends Model
{
    use Searchable;

    protected $table = 'gs_bench_suppliers';

    protected $guarded = [];

    public $timestamps = false;

    protected array $searchable = ['code', 'name'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';

    protected ?string $searchableTypeLabel = 'Supplier';
}

beforeEach(function (): void {
    foreach (['gs_bench_products', 'gs_bench_customers', 'gs_bench_suppliers'] as $table) {
        Schema::create($table, function (Blueprint $b): void {
            $b->id();
            $b->string('code')->index();
            $b->string('name')->index();
            $b->string('email')->nullable();
        });
    }

    // Seed each table with 200 rows so LIKE scopes do real work.
    foreach (['gs_bench_products', 'gs_bench_customers', 'gs_bench_suppliers'] as $table) {
        $rows = [];
        for ($i = 0; $i < 200; $i++) {
            $rows[] = [
                'code' => sprintf('%s-%04d', strtoupper(substr($table, 9, 3)), $i),
                'name' => "Bench $table $i",
                'email' => $table === 'gs_bench_customers' ? "u$i@x.test" : null,
            ];
        }
        \DB::table($table)->insert($rows);
    }

    ModelRegistry::flush();
    ModelRegistry::register([
        'product' => GlobalSearchBenchProduct::class,
        'customer' => GlobalSearchBenchCustomer::class,
        'supplier' => GlobalSearchBenchSupplier::class,
    ]);
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('gs_bench_products');
    Schema::dropIfExists('gs_bench_customers');
    Schema::dropIfExists('gs_bench_suppliers');
});

it('benchmarks GlobalSearch::search with 3 registered models', function () {
    $result = Benchmark::run('GlobalSearch::search (3 models, term=Bench)', fn () => GlobalSearch::search('Bench', 5))
        ->report();

    // 1 query per searchable model. Anything materially above that hints at
    // an N+1 in the searchable scope or related label/subtitle resolvers.
    $result->assertQueriesAtMost(5)->assertFasterThan(300);

    expect($result->value['total'])->toBeGreaterThan(0)
        ->and($result->value['groups'])->toHaveCount(3);
});

it('benchmarks GlobalSearch::search with no match', function () {
    $result = Benchmark::run('GlobalSearch::search (no match)', fn () => GlobalSearch::search('zzz-no-match-zzz', 5))
        ->report();

    // Empty result still runs one query per registered model.
    $result->assertQueriesAtMost(5)->assertFasterThan(300);

    expect($result->value['total'])->toBe(0);
});

it('benchmarks GlobalSearch::search with high per_model', function () {
    $result = Benchmark::run('GlobalSearch::search (per_model=20)', fn () => GlobalSearch::search('Bench', 20))
        ->report();

    $result->assertQueriesAtMost(5)->assertFasterThan(400);
});
