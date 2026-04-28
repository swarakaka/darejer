<?php

declare(strict_types=1);

use Darejer\Concerns\Searchable;
use Darejer\Data\ModelRegistry;
use Darejer\Http\Controllers\SearchController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;

class SearchTestUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

class SearchTestProduct extends Model
{
    use Searchable;

    protected $table = 'search_test_products';

    protected $guarded = [];

    public $timestamps = false;

    protected array $searchable = ['code', 'name'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';

    protected ?string $searchableTypeLabel = 'Product';
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
    });

    Schema::create('search_test_products', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->string('name')->nullable();
    });

    config()->set('auth.providers.users.model', SearchTestUser::class);
    config()->set('cache.default', 'array');

    ModelRegistry::flush();
    ModelRegistry::register(['product' => SearchTestProduct::class]);

    test()->actingAs(SearchTestUser::query()->create(['name' => 'A', 'email' => 'a@x.test']));
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('search_test_products');
    Schema::dropIfExists('users');
});

function searchRequest(string $query): Request
{
    $request = Request::create('/darejer/search', 'GET', ['q' => $query]);
    $request->setUserResolver(fn () => auth()->user());

    return $request;
}

it('returns grouped JSON results from the search controller', function (): void {
    SearchTestProduct::query()->create(['code' => 'P-001', 'name' => 'Acme Widget']);
    SearchTestProduct::query()->create(['code' => 'P-002', 'name' => 'Beta Sprocket']);

    $payload = (new SearchController)->index(searchRequest('acme'))->getData(true);

    expect($payload['query'])->toBe('acme')
        ->and($payload['total'])->toBe(1)
        ->and($payload['groups'])->toHaveCount(1)
        ->and($payload['groups'][0]['slug'])->toBe('product')
        ->and($payload['groups'][0]['type'])->toBe('Product')
        ->and($payload['groups'][0]['items'][0]['label'])->toBe('Acme Widget')
        ->and($payload['groups'][0]['items'][0]['subtitle'])->toBe('P-001');
});

it('returns an empty payload for a blank query', function (): void {
    SearchTestProduct::query()->create(['code' => 'P-001', 'name' => 'Acme']);

    $payload = (new SearchController)->index(searchRequest(''))->getData(true);

    expect($payload)->toMatchArray(['query' => '', 'groups' => [], 'total' => 0]);
});

it('caps results per model when per_model is supplied', function (): void {
    foreach (range(1, 8) as $i) {
        SearchTestProduct::query()->create(['code' => 'P-'.$i, 'name' => "Acme {$i}"]);
    }

    $request = Request::create('/darejer/search', 'GET', ['q' => 'acme', 'per_model' => '2']);
    $request->setUserResolver(fn () => auth()->user());

    $payload = (new SearchController)->index($request)->getData(true);

    expect($payload['total'])->toBe(2)
        ->and($payload['groups'][0]['items'])->toHaveCount(2);
});

it('throttles excessive requests with a 429', function (): void {
    SearchTestProduct::query()->create(['code' => 'P-001', 'name' => 'Acme']);

    $controller = new SearchController;

    // 60 hits/minute is the SearchController's documented limit.
    for ($i = 0; $i < 60; $i++) {
        $controller->index(searchRequest('acme'));
    }

    $response = $controller->index(searchRequest('acme'));

    expect($response->getStatusCode())->toBe(429);

    RateLimiter::clear('darejer-search-'.auth()->id());
});
