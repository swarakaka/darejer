<?php

use Darejer\Navigation\NavigationManager;
use Darejer\Navigation\NavItem;

beforeEach(fn () => NavigationManager::flush());

it('serializes a nav item', function () {
    $item = NavItem::make('Dashboard')->icon('LayoutDashboard')->url('/dashboard');
    $array = $item->toArray();

    expect($array)
        ->toHaveKey('label', 'Dashboard')
        ->toHaveKey('icon', 'LayoutDashboard')
        ->toHaveKey('url', '/dashboard');
});

it('strips invisible nav items', function () {
    $item = NavItem::make('Admin Only')
        ->url('/admin')
        ->canSee('admin.access');

    // Not authenticated — item is hidden.
    expect($item->toArray())->toBeEmpty();
});

it('NavigationManager::toArray strips unauthorized items', function () {
    NavigationManager::define([
        NavItem::make('Home')->url('/')->canSee('home.view'),
    ]);

    // No authenticated user means every canSee() guard fails.
    expect(NavigationManager::toArray())->toHaveCount(0);
});

it('serializes a nav item with children', function () {
    $item = NavItem::make('Catalog')
        ->children([
            NavItem::make('Products')->url('/products'),
            NavItem::make('Categories')->url('/categories'),
        ]);

    $array = $item->toArray();
    expect($array)->toHaveKey('children');
    expect($array['children'])->toHaveCount(2);
});
