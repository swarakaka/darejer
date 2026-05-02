<?php

use Darejer\Actions\ModalToggleAction;
use Darejer\Components\Combobox;
use Darejer\Components\Textarea;
use Darejer\Components\TextInput;
use Darejer\Forms\Form;

it('serializes without a form by default (navigation mode)', function () {
    $action = ModalToggleAction::make('Edit')->url('/things/1/edit');

    $array = $action->toArray();

    expect($array['type'])->toBe('ModalToggle');
    expect($array['dialog'])->toBeTrue();
    expect($array['url'])->toBe('/things/1/edit');
    expect($array)->not->toHaveKey('form');
});

it('embeds an inline form schema when one is attached', function () {
    $form = Form::make('confirm')
        ->title('Confirm')
        ->components([
            Textarea::make('reason')->label('Reason')->required(),
        ])
        ->save('/things/1/do', 'POST', 'Submit');

    $action = ModalToggleAction::make('Do thing')->form($form);

    $array = $action->toArray();

    expect($array['form'])->toBeArray();
    expect($array['form']['title'])->toBe('Confirm');
    expect($array['form']['components'])->toHaveCount(1);
    expect($array['form']['components'][0]['type'])->toBe('Textarea');
    expect($array['form']['components'][0]['name'])->toBe('reason');
    expect($array['form']['components'][0]['required'])->toBeTrue();

    $save = collect($array['form']['actions'])->firstWhere('type', 'Save');
    expect($save['url'])->toBe('/things/1/do');
    expect($save['method'])->toBe('POST');
    expect($save['label'])->toBe('Submit');
});

it('supports any darejer form component as an inline form input', function () {
    $form = Form::make('mixed')
        ->title('Mixed')
        ->components([
            TextInput::make('name'),
            Textarea::make('notes'),
            Combobox::make('owner_id'),
        ])
        ->save('/x', 'POST');

    $action = ModalToggleAction::make('Open')->form($form);

    $types = array_column($action->toArray()['form']['components'], 'type');

    expect($types)->toBe(['TextInput', 'Textarea', 'Combobox']);
});

it('carries the modal size into the serialized payload', function () {
    $form = Form::make('x')->title('X')->save('/x', 'POST');

    $action = ModalToggleAction::make('Open')->size('lg')->form($form);

    expect($action->toArray()['modalSize'])->toBe('lg');
});
