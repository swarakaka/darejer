<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

it('serves package validation translations for locales the host has not translated', function () {
    App::setLocale('ckb');

    $validator = Validator::make([], ['name' => 'required']);

    expect($validator->errors()->first('name'))
        ->toBe('خانەی name پێویستە.');
});

it('still resolves namespaced darejer translations', function () {
    App::setLocale('ckb');

    expect(trans('darejer::validation.required'))
        ->toBe('خانەی :attribute پێویستە.');
});

it('lets the host override package translations via its own lang/{locale}/validation.php', function () {
    $hostLang = lang_path('ckb');
    @mkdir($hostLang, 0o755, true);
    file_put_contents($hostLang.'/validation.php', "<?php\n\nreturn ['required' => 'host override for :attribute'];\n");

    try {
        App::setLocale('ckb');

        $validator = Validator::make([], ['name' => 'required']);

        expect($validator->errors()->first('name'))
            ->toBe('host override for name');
    } finally {
        @unlink($hostLang.'/validation.php');
        @rmdir($hostLang);
    }
});
