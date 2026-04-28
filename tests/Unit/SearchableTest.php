<?php

declare(strict_types=1);

use Darejer\Concerns\Searchable;
use Darejer\Data\ModelRegistry;
use Darejer\Support\GlobalSearch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Translatable\HasTranslations;

/**
 * Top-level so PHP autoload doesn't try to resolve the file.
 */
class SearchableTestWidget extends Model
{
    use Searchable;

    protected $table = 'searchable_widgets';

    protected $guarded = [];

    public $timestamps = false;

    protected array $searchable = ['code', 'name', 'email'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';

    protected ?string $searchableTypeLabel = 'Widget';
}

class SearchableTestPlain extends Model
{
    protected $table = 'searchable_plain';

    protected $guarded = [];

    public $timestamps = false;
}

class SearchableTestTranslatable extends Model
{
    use HasTranslations, Searchable;

    protected $table = 'searchable_translatable';

    protected $guarded = [];

    public $timestamps = false;

    public array $translatable = ['name'];

    protected array $searchable = ['code', 'name'];

    protected ?string $searchableLabel = 'name';

    protected ?string $searchableSubtitle = 'code';
}

beforeEach(function (): void {
    Schema::create('searchable_widgets', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->string('name')->nullable();
        $table->string('email')->nullable();
    });

    Schema::create('searchable_plain', function (Blueprint $table): void {
        $table->id();
        $table->string('label')->nullable();
    });

    Schema::create('searchable_translatable', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->json('name')->nullable();
    });

    config()->set('darejer.languages', ['en', 'ar']);

    ModelRegistry::flush();
});

afterEach(function (): void {
    ModelRegistry::flush();
    Schema::dropIfExists('searchable_widgets');
    Schema::dropIfExists('searchable_plain');
    Schema::dropIfExists('searchable_translatable');
});

it('matches records via the LIKE scope across every searchable column', function (): void {
    SearchableTestWidget::query()->create(['code' => 'W-001', 'name' => 'Acme Corp', 'email' => 'hi@acme.test']);
    SearchableTestWidget::query()->create(['code' => 'W-002', 'name' => 'Beta Inc', 'email' => 'hi@beta.test']);
    SearchableTestWidget::query()->create(['code' => 'XYZ',   'name' => 'No match', 'email' => 'acme-lookup@x.test']);

    // Matches via `name` (Acme Corp) AND via `email` (acme-lookup@x.test).
    $hits = SearchableTestWidget::query()->searchableTerm('acme')->get();

    expect($hits)->toHaveCount(2)
        ->and($hits->pluck('code')->all())->toContain('W-001', 'XYZ');
});

it('escapes LIKE wildcards in the search term', function (): void {
    SearchableTestWidget::query()->create(['code' => 'A_1', 'name' => 'Underscore']);
    SearchableTestWidget::query()->create(['code' => 'AB1', 'name' => 'AnyChar']);

    // Without escaping, `_` would match any character and return both rows.
    $hits = SearchableTestWidget::query()->searchableTerm('A_1')->get();

    expect($hits)->toHaveCount(1)
        ->and($hits->first()->code)->toBe('A_1');
});

it('returns an empty result when the term is blank', function (): void {
    SearchableTestWidget::query()->create(['code' => 'W-001', 'name' => 'Acme']);

    expect(SearchableTestWidget::query()->searchableTerm('')->count())
        ->toBe(SearchableTestWidget::query()->count());
});

it('exposes label, subtitle and type via the trait helpers', function (): void {
    $widget = SearchableTestWidget::query()->create([
        'code' => 'W-001',
        'name' => 'Acme Corp',
        'email' => 'hi@acme.test',
    ]);

    expect($widget->getSearchableAttributes())->toBe(['code', 'name', 'email'])
        ->and($widget->getSearchableLabel())->toBe('Acme Corp')
        ->and($widget->getSearchableSubtitle())->toBe('W-001')
        ->and($widget->getSearchableTypeLabel())->toBe('Widget');
});

it('resolves the searchable URL only when a matching named route exists', function (): void {
    $widget = SearchableTestWidget::query()->create(['code' => 'W-001', 'name' => 'Acme']);

    expect($widget->getSearchableUrl())->toBeNull();

    Route::get('/widgets/{id}', fn ($id) => $id)->name('searchable-test-widgets.show');
    Route::getRoutes()->refreshNameLookups();

    $url = $widget->getSearchableUrl();
    expect($url)->toBeString()
        ->and(str_ends_with((string) $url, '/widgets/'.$widget->id))->toBeTrue();
});

it('GlobalSearch::isSearchable detects the trait', function (): void {
    expect(GlobalSearch::isSearchable(SearchableTestWidget::class))->toBeTrue()
        ->and(GlobalSearch::isSearchable(SearchableTestPlain::class))->toBeFalse()
        ->and(GlobalSearch::isSearchable('Some\\Missing\\Class'))->toBeFalse();
});

it('GlobalSearch::search groups hits by model and skips non-searchable registrations', function (): void {
    SearchableTestWidget::query()->create(['code' => 'W-001', 'name' => 'Acme Corp']);
    SearchableTestWidget::query()->create(['code' => 'W-002', 'name' => 'Beta Inc']);
    SearchableTestPlain::query()->create(['label' => 'Acme — should not appear']);

    ModelRegistry::register([
        'widget' => SearchableTestWidget::class,
        'plain' => SearchableTestPlain::class,
    ]);

    $result = GlobalSearch::search('acme');

    expect($result['query'])->toBe('acme')
        ->and($result['total'])->toBe(1)
        ->and($result['groups'])->toHaveCount(1)
        ->and($result['groups'][0]['slug'])->toBe('widget')
        ->and($result['groups'][0]['type'])->toBe('Widget')
        ->and($result['groups'][0]['items'][0]['label'])->toBe('Acme Corp')
        ->and($result['groups'][0]['items'][0]['subtitle'])->toBe('W-001');
});

it('GlobalSearch::searchableModels dedupes alias slugs to one entry per class', function (): void {
    ModelRegistry::register([
        'widget' => SearchableTestWidget::class,
        'widgets' => SearchableTestWidget::class,
        'wgt' => SearchableTestWidget::class,
    ]);

    $models = GlobalSearch::searchableModels();

    expect($models)->toHaveCount(1)
        ->and(array_values($models))->toBe([SearchableTestWidget::class]);
});

it('GlobalSearch::search returns nothing for a blank term', function (): void {
    ModelRegistry::register(['widget' => SearchableTestWidget::class]);
    SearchableTestWidget::query()->create(['code' => 'W-001', 'name' => 'Acme']);

    expect(GlobalSearch::search(''))
        ->toMatchArray(['query' => '', 'groups' => [], 'total' => 0]);
});

it('matches translatable JSON columns across every configured locale', function (): void {
    $arOnly = new SearchableTestTranslatable(['code' => 'T-AR']);
    $arOnly->setTranslation('name', 'ar', 'سارا');
    $arOnly->save();

    $enOnly = new SearchableTestTranslatable(['code' => 'T-EN']);
    $enOnly->setTranslation('name', 'en', 'Sara');
    $enOnly->save();

    SearchableTestTranslatable::query()->create(['code' => 'T-NA', 'name' => null]);

    expect(SearchableTestTranslatable::query()->searchableTerm('سارا')->pluck('code')->all())->toBe(['T-AR'])
        ->and(SearchableTestTranslatable::query()->searchableTerm('Sara')->pluck('code')->all())->toBe(['T-EN'])
        ->and(SearchableTestTranslatable::query()->searchableTerm('T-AR')->pluck('code')->all())->toBe(['T-AR']);
});

it('falls back to any non-empty locale when rendering a translatable label', function (): void {
    app()->setLocale('en');

    $row = new SearchableTestTranslatable(['code' => 'T-AR']);
    $row->setTranslation('name', 'ar', 'سارا');
    $row->save();

    expect($row->getSearchableLabel())->toBe('سارا');
});

it('GlobalSearch::search caps results per model via the perModel argument', function (): void {
    ModelRegistry::register(['widget' => SearchableTestWidget::class]);

    foreach (range(1, 12) as $i) {
        SearchableTestWidget::query()->create(['code' => 'W-'.$i, 'name' => "Acme {$i}"]);
    }

    $result = GlobalSearch::search('acme', perModel: 3);

    expect($result['total'])->toBe(3)
        ->and($result['groups'][0]['items'])->toHaveCount(3);
});
