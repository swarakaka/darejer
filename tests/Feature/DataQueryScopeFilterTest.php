<?php

declare(strict_types=1);

use Darejer\Data\ModelRegistry;
use Darejer\Http\Controllers\DataController;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class ScopeFilterUser extends Authenticatable
{
    protected $table = 'users';

    protected $guarded = [];

    public $timestamps = false;
}

class ScopeFilterItem extends Model
{
    protected $table = 'scope_filter_items';

    protected $fillable = ['code', 'name'];

    public $timestamps = false;

    #[Scope]
    protected function filterWarehouseId(Builder $query, int|string $warehouseId): Builder
    {
        return $query->whereIn('id', function ($q) use ($warehouseId): void {
            $q->select('item_id')
                ->from('scope_filter_stock')
                ->where('warehouse_id', $warehouseId);
        });
    }
}

beforeEach(function (): void {
    Schema::create('users', function (Blueprint $table): void {
        $table->id();
        $table->string('email')->nullable();
    });

    Schema::create('scope_filter_items', function (Blueprint $table): void {
        $table->id();
        $table->string('code');
        $table->string('name');
    });

    Schema::create('scope_filter_stock', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('item_id');
        $table->unsignedBigInteger('warehouse_id');
    });

    config()->set('auth.providers.users.model', ScopeFilterUser::class);
    config()->set('cache.default', 'array');

    ModelRegistry::flush();
    ModelRegistry::register(['scopefilteritem' => ScopeFilterItem::class]);

    test()->actingAs(ScopeFilterUser::query()->create(['email' => 'a@x.test']));
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('scope_filter_stock');
    Schema::dropIfExists('scope_filter_items');
    Schema::dropIfExists('users');
});

it('falls back to scopeFilter{Field} for non-fillable filter keys', function (): void {
    $a = ScopeFilterItem::query()->create(['code' => 'A', 'name' => 'Alpha']);
    $b = ScopeFilterItem::query()->create(['code' => 'B', 'name' => 'Beta']);
    ScopeFilterItem::query()->create(['code' => 'C', 'name' => 'Gamma']);

    // a and b have stock in warehouse 1; c only in warehouse 2.
    \DB::table('scope_filter_stock')->insert([
        ['item_id' => $a->id, 'warehouse_id' => 1],
        ['item_id' => $b->id, 'warehouse_id' => 1],
    ]);

    $request = Request::create('/darejer/data/scopefilteritem', 'GET', [
        'filters' => ['warehouse_id' => '1'],
    ]);
    $request->setUserResolver(fn () => auth()->user());

    $payload = (new DataController)->index($request, 'scopefilteritem')->getData(true);

    $codes = collect($payload['data'])->pluck('code')->all();

    expect($codes)->toContain('A')
        ->and($codes)->toContain('B')
        ->and($codes)->not->toContain('C');
});

it('ignores filter keys that are neither fillable nor a scope', function (): void {
    ScopeFilterItem::query()->create(['code' => 'A', 'name' => 'Alpha']);

    $request = Request::create('/darejer/data/scopefilteritem', 'GET', [
        'filters' => ['nonsense_field' => '1'],
    ]);
    $request->setUserResolver(fn () => auth()->user());

    $payload = (new DataController)->index($request, 'scopefilteritem')->getData(true);

    expect($payload['data'])->toHaveCount(1);
});

it('skips empty scope filter values', function (): void {
    $a = ScopeFilterItem::query()->create(['code' => 'A', 'name' => 'Alpha']);
    $b = ScopeFilterItem::query()->create(['code' => 'B', 'name' => 'Beta']);

    \DB::table('scope_filter_stock')->insert([
        ['item_id' => $a->id, 'warehouse_id' => 1],
    ]);

    $request = Request::create('/darejer/data/scopefilteritem', 'GET', [
        'filters' => ['warehouse_id' => ''],
    ]);
    $request->setUserResolver(fn () => auth()->user());

    $payload = (new DataController)->index($request, 'scopefilteritem')->getData(true);

    // Empty filter value short-circuits — both rows come back.
    expect(collect($payload['data'])->pluck('code')->all())
        ->toContain('A')
        ->toContain('B');
});
