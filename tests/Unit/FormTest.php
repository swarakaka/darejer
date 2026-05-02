<?php

use Darejer\Components\Combobox;
use Darejer\Components\DatePicker;
use Darejer\Components\SelectComponent;
use Darejer\Components\Textarea;
use Darejer\Components\TextInput;
use Darejer\Forms\Form;
use Inertia\Response;

function inertiaProps(Response $response): array
{
    $reflection = new ReflectionClass($response);
    $prop = $reflection->getProperty('props');
    $prop->setAccessible(true);

    return $prop->getValue($response);
}

it('renders a form as a dialog screen', function () {
    $response = Form::make('confirm')
        ->title('Confirm action')
        ->components([
            Textarea::make('reason')->label('Reason')->required(),
        ])
        ->save('/things/1/do', 'POST', 'Submit')
        ->renderAsDialog('sm');

    $props = inertiaProps($response);

    expect($props['dialog'])->toBeTrue();
    expect($props['dialogSize'])->toBe('sm');
    expect($props['title'])->toBe('Confirm action');
    expect($props['components'])->toHaveCount(1);
    expect($props['components'][0]['type'])->toBe('Textarea');
    expect($props['actions'])->toContain(
        // Save action serialized
        ...array_filter($props['actions'], fn ($a) => ($a['type'] ?? null) === 'Save'),
    );
});

it('keeps page mode flagged off when rendered as a screen', function () {
    $response = Form::make('default')
        ->title('Edit')
        ->components([TextInput::make('code')])
        ->save('/things/1', 'PUT')
        ->renderAsScreen();

    $props = inertiaProps($response);

    expect($props['dialog'])->toBeFalse();
});

it('accepts every darejer form component type in a dialog', function () {
    $response = Form::make('mixed')
        ->title('Mixed')
        ->components([
            TextInput::make('name'),
            Textarea::make('notes'),
            DatePicker::make('starts_at'),
            SelectComponent::make('status')->options(['draft' => 'Draft']),
            Combobox::make('owner_id'),
        ])
        ->save('/x', 'POST')
        ->renderAsDialog();

    $types = array_column(inertiaProps($response)['components'], 'type');

    expect($types)->toBe(['TextInput', 'Textarea', 'DatePicker', 'Select', 'Combobox']);
});
