<?php

use Darejer\Actions\SaveAction;
use Darejer\Components\TextInput;
use Darejer\Screen\Screen;
use Darejer\Screen\Section;
use Darejer\Screen\Tab;

it('serializes a screen to array', function () {
    $screen = Screen::make('Test Screen')
        ->components([
            TextInput::make('name')->label('Name'),
        ])
        ->actions([
            SaveAction::make()->url('/test'),
        ]);

    $array = $screen->toArray();

    expect($array)
        ->toHaveKey('title', 'Test Screen')
        ->toHaveKey('components')
        ->toHaveKey('actions')
        ->toHaveKey('dialog', false);

    expect($array['components'])->toHaveCount(1);
    expect($array['components'][0])->toHaveKey('type', 'TextInput');
    expect($array['actions'])->toHaveCount(1);
    expect($array['actions'][0])->toHaveKey('type', 'Save');
});

it('strips components hidden by canSee', function () {
    $screen = Screen::make('Test')
        ->components([
            TextInput::make('visible')->label('Visible'),
            TextInput::make('hidden')->label('Hidden')->canSee('nonexistent.permission'),
        ]);

    $array = $screen->toArray();

    expect($array['components'])->toHaveCount(1);
    expect($array['components'][0]['name'])->toBe('visible');
});

it('serializes dialog mode', function () {
    $screen = Screen::make('Dialog Screen')->dialog('lg');
    $array = $screen->toArray();

    expect($array['dialog'])->toBeTrue();
    expect($array['dialogSize'])->toBe('lg');
});

it('serializes tabs from Tab instances', function () {
    $screen = Screen::make('Test')
        ->components([
            TextInput::make('name')->label('Name'),
            TextInput::make('sku')->label('SKU'),
        ])
        ->tabs([
            Tab::make('Details')->components(['name']),
            Tab::make('Inventory')->components(['sku']),
        ]);

    $array = $screen->toArray();

    expect($array['tabs'])->toHaveCount(2);
    expect($array['tabs'][0]['title'])->toBe('Details');
    expect($array['tabs'][1]['components'])->toBe(['sku']);
    expect($array['sections'])->toBeNull();
});

it('serializes sections from Section instances', function () {
    $screen = Screen::make('Test')
        ->components([
            TextInput::make('name')->label('Name'),
            TextInput::make('sku')->label('SKU'),
        ])
        ->sections([
            Section::make('General')->components(['name']),
            Section::make('Inventory')->components(['sku'])->collapsed(),
        ]);

    $array = $screen->toArray();

    expect($array['sections'])->toHaveCount(2);
    expect($array['sections'][0]['title'])->toBe('General');
    expect($array['sections'][0]['collapsed'])->toBeFalse();
    expect($array['sections'][1]['collapsed'])->toBeTrue();
});

it('rejects array form for sections', function () {
    Screen::make('Test')->sections([
        ['title' => 'General', 'components' => ['name']],
    ]);
})->throws(InvalidArgumentException::class);

it('rejects array form for tabs', function () {
    Screen::make('Test')->tabs([
        ['title' => 'Details', 'components' => ['name']],
    ]);
})->throws(InvalidArgumentException::class);

it('returns null tabs when none defined', function () {
    $screen = Screen::make('Test');
    expect($screen->toArray()['tabs'])->toBeNull();
});

it('serializes breadcrumbs', function () {
    $screen = Screen::make('Test')
        ->breadcrumbs([
            ['label' => 'Home', 'url' => '/'],
            ['label' => 'Products'],
        ]);

    $array = $screen->toArray();

    expect($array['breadcrumbs'])->toHaveCount(2);
    expect($array['breadcrumbs'][0]['label'])->toBe('Home');
});
