<?php

use Darejer\Actions\ButtonAction;
use Darejer\Components\TextInput;
use Darejer\Screen\Screen;

it('returns null when visible() permission string fails', function () {
    $action = ButtonAction::make('Soft-Close')
        ->url('/x')
        ->visible('nonexistent.permission');

    expect($action->toArray())->toBeNull();
});

it('returns null when visible() closure returns false', function () {
    $action = ButtonAction::make('Soft-Close')
        ->url('/x')
        ->visible(fn () => false);

    expect($action->toArray())->toBeNull();
});

it('serializes when visible() closure returns true', function () {
    $action = ButtonAction::make('Soft-Close')
        ->url('/x')
        ->visible(fn () => true);

    expect($action->toArray())
        ->toBeArray()
        ->toHaveKey('label', 'Soft-Close');
});

it('passes the parent record into the visible() closure on actions', function () {
    $action = ButtonAction::make('Soft-Close')
        ->url('/x')
        ->visible(fn ($record) => ($record['status'] ?? null) === 'open');

    $screen = Screen::make('Period')
        ->record(['status' => 'open'])
        ->actions([$action]);

    $array = $screen->toArray();

    expect($array['actions'])->toHaveCount(1);
    expect($array['actions'][0])->toHaveKey('label', 'Soft-Close');
});

it('hides actions when the parent record fails the visible() closure', function () {
    $action = ButtonAction::make('Reopen')
        ->url('/x')
        ->visible(fn ($record) => ($record['status'] ?? null) === 'closed');

    $screen = Screen::make('Period')
        ->record(['status' => 'open'])
        ->actions([$action]);

    expect($screen->toArray()['actions'])->toBe([]);
});

it('passes the parent record into the visible() closure on components', function () {
    $component = TextInput::make('reason')
        ->visible(fn ($record) => ($record['status'] ?? null) === 'closed');

    $screen = Screen::make('Period')
        ->record(['status' => 'closed'])
        ->components([$component]);

    expect($screen->toArray()['components'])->toHaveCount(1);
    expect($screen->toArray()['components'][0])->toHaveKey('name', 'reason');
});

it('strips components when their visible() closure returns false', function () {
    $component = TextInput::make('reason')
        ->visible(fn ($record) => ($record['status'] ?? null) === 'closed');

    $screen = Screen::make('Period')
        ->record(['status' => 'open'])
        ->components([$component]);

    expect($screen->toArray()['components'])->toBe([]);
});

it('passes the user into the visible() closure', function () {
    $captured = null;

    $action = ButtonAction::make('X')
        ->url('/x')
        ->visible(function ($record, $user) use (&$captured) {
            $captured = func_get_args();

            return true;
        });

    Screen::make('S')->actions([$action])->toArray();

    // No authenticated user in tests — second arg should be null but signature honored.
    expect($captured)->toHaveCount(2);
    expect($captured[1])->toBeNull();
});

it('treats visible() and canSee() as independent gates that must both pass', function () {
    $action = ButtonAction::make('X')
        ->url('/x')
        ->visible(fn () => true)
        ->canSee('nonexistent.permission');

    expect($action->toArray())->toBeNull();

    $action2 = ButtonAction::make('Y')
        ->url('/x')
        ->visible(fn () => false)
        ->canSee(fn () => true);

    expect($action2->toArray())->toBeNull();
});
