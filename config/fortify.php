<?php

use Laravel\Fortify\Features;

return [

    /*
    |--------------------------------------------------------------------------
    | Fortify Guard
    |--------------------------------------------------------------------------
    */
    'guard' => 'web',

    /*
    |--------------------------------------------------------------------------
    | Fortify Password Broker
    |--------------------------------------------------------------------------
    */
    'passwords' => 'users',

    /*
    |--------------------------------------------------------------------------
    | Username / Email
    |--------------------------------------------------------------------------
    */
    'username' => 'username',
    'email'    => 'email',

    /*
    |--------------------------------------------------------------------------
    | Lowercase Usernames
    |--------------------------------------------------------------------------
    */
    'lowercase_usernames' => true,

    /*
    |--------------------------------------------------------------------------
    | Home Path
    |--------------------------------------------------------------------------
    | Where users are redirected after login. Kept in sync with
    | `darejer.home_route` so Darejer owns a single source of truth.
    */
    'home' => env('FORTIFY_HOME', '/darejer'),

    /*
    |--------------------------------------------------------------------------
    | Route Prefix / Domain / Middleware
    |--------------------------------------------------------------------------
    | Fortify's auth routes are mounted under the same prefix as the rest of
    | the Darejer app so URLs look like `/darejer/login`, `/darejer/logout`,
    | `/darejer/forgot-password`, etc.
    */
    'prefix'     => env('FORTIFY_PREFIX', 'darejer'),
    'domain'     => null,
    'middleware' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'limiters' => [
        'login'      => 'login',
        'two-factor' => 'two-factor',
    ],

    /*
    |--------------------------------------------------------------------------
    | Register View Routes
    |--------------------------------------------------------------------------
    | Fortify's GET routes render Inertia pages. Darejer wires these up via
    | `Fortify::loginView()` etc. in `Darejer\Providers\FortifyServiceProvider`.
    */
    'views' => true,

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    | Darejer defaults: registration disabled (CLAUDE.md rule), password
    | reset + profile/password updates + 2FA enabled. Host apps can override
    | any of this by publishing `config/fortify.php`.
    */
    'features' => [
        // Features::registration(),
        Features::resetPasswords(),
        // Features::emailVerification(),
        Features::updateProfileInformation(),
        Features::updatePasswords(),
        Features::twoFactorAuthentication([
            'confirm'        => true,
            'confirmPassword' => true,
        ]),
    ],

];
