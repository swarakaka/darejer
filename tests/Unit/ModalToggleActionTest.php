<?php

use Darejer\Actions\ModalToggleAction;
use Darejer\Components\Combobox;
use Darejer\Components\Textarea;
use Darejer\Components\TextInput;

it('serializes without a form by default (navigation mode)', function () {
    $action = ModalToggleAction::make('Edit')->url('/things/1/edit');

    $array = $action->toArray();

    expect($array['type'])->toBe('ModalToggle');
    expect($array['dialog'])->toBeTrue();
    expect($array['url'])->toBe('/things/1/edit');
    expect($array)->not->toHaveKey('form');
});

it('builds an inline form schema from a components array', function () {
    $action = ModalToggleAction::make('Soft-Close')
        ->url('/things/1/soft-close')
        ->method('POST')
        ->form([
            Textarea::make('reason')->label('Reason')->required(),
        ]);

    $array = $action->toArray();

    expect($array['form'])->toBeArray();
    expect($array['form']['title'])->toBe('Soft-Close');
    expect($array['form']['components'])->toHaveCount(1);
    expect($array['form']['components'][0]['type'])->toBe('Textarea');
    expect($array['form']['components'][0]['name'])->toBe('reason');
    expect($array['form']['components'][0]['required'])->toBeTrue();

    $save = collect($array['form']['actions'])->firstWhere('type', 'Save');
    expect($save['url'])->toBe('/things/1/soft-close');
    expect($save['method'])->toBe('POST');
    expect($save['label'])->toBe('Soft-Close');
});

it('lets formTitle override the dialog title', function () {
    $action = ModalToggleAction::make('Soft-Close')
        ->url('/x')->method('POST')
        ->formTitle('Soft-Close Period — 2026-04')
        ->form([Textarea::make('reason')]);

    expect($action->toArray()['form']['title'])->toBe('Soft-Close Period — 2026-04');
});

it('supports any darejer form component as an inline form input', function () {
    $action = ModalToggleAction::make('Open')
        ->url('/x')->method('POST')
        ->form([
            TextInput::make('name'),
            Textarea::make('notes'),
            Combobox::make('owner_id'),
        ]);

    $types = array_column($action->toArray()['form']['components'], 'type');

    expect($types)->toBe(['TextInput', 'Textarea', 'Combobox']);
});

it('carries the modal size into the serialized payload', function () {
    $action = ModalToggleAction::make('Open')
        ->url('/x')->method('POST')->size('lg')
        ->form([Textarea::make('reason')]);

    expect($action->toArray()['modalSize'])->toBe('lg');
});
