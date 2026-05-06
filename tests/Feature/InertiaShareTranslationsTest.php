<?php

declare(strict_types=1);

use Darejer\Http\Middleware\HandleInertiaRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

it('shares the host app translations file for the current locale on every Inertia response', function (): void {
    $path = lang_path('ckb.json');
    file_put_contents($path, json_encode(['Voucher No' => 'ژمارەی سند']));

    try {
        config()->set('darejer.languages', ['en', 'ckb']);
        App::setLocale('ckb');

        $shared = (new HandleInertiaRequests)->share(Request::create('/'));

        $darejer = ($shared['darejer'])();

        expect($darejer['translations'])->toBe(['Voucher No' => 'ژمارەی سند']);
    } finally {
        @unlink($path);
    }
});

it('returns an empty translations bag when the host has no lang/{locale}.json file', function (): void {
    config()->set('darejer.languages', ['en', 'fr']);
    App::setLocale('fr');

    $shared = (new HandleInertiaRequests)->share(Request::create('/'));

    expect(($shared['darejer'])()['translations'])->toBe([]);
});
