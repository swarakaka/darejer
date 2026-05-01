<?php

declare(strict_types=1);

use Darejer\Data\DataTransformer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Translatable\HasTranslations;

class DataTransformerCategoryFixture extends Model
{
    use HasTranslations;

    protected $table = 'data_transformer_categories';

    protected $guarded = [];

    public $timestamps = false;

    public array $translatable = ['name'];
}

beforeEach(function (): void {
    Schema::create('data_transformer_categories', function (Blueprint $table): void {
        $table->id();
        $table->string('code')->nullable();
        $table->json('name')->nullable();
    });

    app()->setLocale('en');
});

afterEach(function (): void {
    Schema::dropIfExists('data_transformer_categories');
});

it('returns a single label field as the combobox label', function (): void {
    $cat = DataTransformerCategoryFixture::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Products', 'ar' => 'المنتجات'],
    ]);

    $transformer = new DataTransformer(
        DataTransformerCategoryFixture::class,
        'id',
        'code',
        true,
    );

    expect($transformer->transform($cat))->toMatchArray([
        'value' => (string) $cat->id,
        'label' => 'CAT-PROD',
    ]);
});

it('composes a multi-field label with the configured separator', function (): void {
    $cat = DataTransformerCategoryFixture::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Products', 'ar' => 'المنتجات'],
    ]);

    $transformer = new DataTransformer(
        DataTransformerCategoryFixture::class,
        'id',
        'code',
        true,
        ['code', 'name'],
        ' — ',
    );

    expect($transformer->transform($cat))->toMatchArray([
        'value' => (string) $cat->id,
        'label' => 'CAT-PROD — Products',
    ]);
});

it('skips empty parts when composing a multi-field label', function (): void {
    $cat = DataTransformerCategoryFixture::query()->create([
        'code' => 'CAT-PROD',
        'name' => null,
    ]);

    $transformer = new DataTransformer(
        DataTransformerCategoryFixture::class,
        'id',
        'code',
        true,
        ['code', 'name'],
    );

    expect($transformer->transform($cat)['label'])->toBe('CAT-PROD');
});

it('flattens translatable JSON to the active locale before composing', function (): void {
    $cat = DataTransformerCategoryFixture::query()->create([
        'code' => 'CAT-PROD',
        'name' => ['en' => 'Products', 'ar' => 'المنتجات'],
    ]);

    app()->setLocale('ar');

    $transformer = new DataTransformer(
        DataTransformerCategoryFixture::class,
        'id',
        'code',
        true,
        ['code', 'name'],
    );

    expect($transformer->transform($cat)['label'])->toBe('CAT-PROD — المنتجات');
});
