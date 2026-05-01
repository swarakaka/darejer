<?php

declare(strict_types=1);

use Darejer\Data\ModelRegistry;
use Darejer\Http\Controllers\DataController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Spatie\Translatable\HasTranslations;

class DataControllerUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

class DataControllerCategory extends Model
{
    use HasTranslations;

    protected $table = 'data_controller_categories';

    protected $guarded = [];

    public $timestamps = false;

    public array $translatable = ['name'];
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
    });

    Schema::create('data_controller_categories', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->json('name')->nullable();
    });

    config()->set('auth.providers.users.model', DataControllerUser::class);
    config()->set('darejer.languages', ['en', 'ar']);
    config()->set('cache.default', 'array');

    ModelRegistry::flush();
    ModelRegistry::register(['itemcategory' => DataControllerCategory::class]);

    test()->actingAs(DataControllerUser::query()->create(['email' => 'a@x.test']));

    app()->setLocale('en');
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('data_controller_categories');
    Schema::dropIfExists('users');
});

function comboboxRequest(array $params): Request
{
    $request = Request::create('/darejer/data/itemcategory', 'GET', array_merge([
        'combobox' => '1',
    ], $params));
    $request->setUserResolver(fn () => auth()->user());

    return $request;
}

it('returns labels composed from multiple fields with spaces around the separator', function (): void {
    DataControllerCategory::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Products', 'ar' => 'المنتجات'],
    ]);

    $request = comboboxRequest([
        'key' => 'id',
        'label_fields' => ['code', 'name'],
    ]);

    $payload = (new DataController)->index($request, 'itemcategory')->getData(true);

    expect($payload['data'])->toHaveCount(1)
        // Spaces on both sides of the em-dash. The separator is hardcoded
        // server-side because Laravel's TrimStrings middleware would strip
        // the leading and trailing spaces if it were sent over the wire.
        ->and($payload['data'][0]['label'])->toBe('CAT-PROD — Products');
});

it('searches across all label fields with OR semantics', function (): void {
    DataControllerCategory::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Products'],
    ]);
    DataControllerCategory::query()->create([
        'code' => 'CAT-SVC',
        'name' => ['en' => 'Services'],
    ]);

    // Search by code — should match CAT-PROD only.
    $request = comboboxRequest([
        'label_fields' => ['code', 'name'],
        'search' => 'PROD',
    ]);

    $payload = (new DataController)->index($request, 'itemcategory')->getData(true);

    expect($payload['data'])->toHaveCount(1)
        ->and($payload['data'][0]['label'])->toContain('CAT-PROD');

    // Search by translatable name — should match Services row.
    $request = comboboxRequest([
        'label_fields' => ['code', 'name'],
        'search' => 'Service',
    ]);

    $payload = (new DataController)->index($request, 'itemcategory')->getData(true);

    expect($payload['data'])->toHaveCount(1)
        ->and($payload['data'][0]['label'])->toContain('CAT-SVC');
});

it('restricts the result set to the given ids[] filter', function (): void {
    $a = DataControllerCategory::query()->create([
        'code' => 'CAT-A', 'name' => ['en' => 'Alpha'],
    ]);
    DataControllerCategory::query()->create([
        'code' => 'CAT-B', 'name' => ['en' => 'Beta'],
    ]);
    $c = DataControllerCategory::query()->create([
        'code' => 'CAT-C', 'name' => ['en' => 'Gamma'],
    ]);

    $request = comboboxRequest([
        'ids' => [(string) $a->id, (string) $c->id],
    ]);

    $payload = (new DataController)->index($request, 'itemcategory')->getData(true);

    $labels = collect($payload['data'])->pluck('label')->all();

    expect($payload['data'])->toHaveCount(2)
        ->and($labels)->toContain('Alpha')
        ->and($labels)->toContain('Gamma')
        ->and($labels)->not->toContain('Beta');
});

it('honors search_fields override when broader than the displayed fields', function (): void {
    DataControllerCategory::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Findable Note'],
    ]);

    // Display only `code`, but allow search across `name` too.
    $request = comboboxRequest([
        'label' => 'code',
        'search_fields' => ['code', 'name'],
        'search' => 'Findable',
    ]);

    $payload = (new DataController)->index($request, 'itemcategory')->getData(true);

    expect($payload['data'])->toHaveCount(1)
        ->and($payload['data'][0]['label'])->toBe('CAT-PROD');
});
