<?php

use Darejer\Http\Controllers\Admin\UserController;
use Darejer\Http\Controllers\DashboardController;

return [

    /*
    |--------------------------------------------------------------------------
    | Languages
    |--------------------------------------------------------------------------
    | List all languages the platform should support.
    | The frontend reads this via Inertia shared props.
    | TranslatableInput and TranslatableTextarea only show multi-language UI
    | when more than one language is listed here.
    |
    | Example: ['en', 'ar', 'de', 'ckb']
    */
    'languages' => ['en'],

    'default_language' => 'en',

    /*
    |--------------------------------------------------------------------------
    | App Name
    |--------------------------------------------------------------------------
    | Wordmark shown in the topbar and used as the suffix on every page
    | <title> (e.g. "Customers - Syntax CRM"). Host apps typically point this
    | at their `app.name` config so it follows `APP_NAME` from `.env`.
    */
    'app_name' => env('APP_NAME', 'Darejer'),

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'guard' => 'web',
    ],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */
    'pagination' => [
        'per_page' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | File Uploads
    |--------------------------------------------------------------------------
    */
    'uploads' => [
        'disk' => 'public',
        'path' => 'uploads',
    ],

    /*
    |--------------------------------------------------------------------------
    | LibreOffice (document → PDF rendering)
    |--------------------------------------------------------------------------
    | DocumentRenderer converts filled .docx templates to PDF with headless
    | LibreOffice for full fidelity (alignment, fonts, borders, RTL). Leave
    | `binary` null to auto-detect `soffice`/`libreoffice` on the PATH and the
    | common install locations; set LIBREOFFICE_BINARY to pin an exact path.
    | When no binary is found it falls back to the pure-PHP mpdf renderer.
    */
    'libreoffice' => [
        'binary' => env('LIBREOFFICE_BINARY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Package Route Prefix
    |--------------------------------------------------------------------------
    */
    'route_prefix' => 'darejer',

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    | Applied to all Darejer routes.
    */
    'middleware' => ['web', 'auth'],

    /*
    |--------------------------------------------------------------------------
    | Home Route
    |--------------------------------------------------------------------------
    | Path users land on after successful login when they had no intended URL.
    | Stored as a path (not a route name) so it can be used as the default
    | argument to redirect()->intended() without eagerly calling route().
    */
    'home_route' => '/darejer',

    /*
    |--------------------------------------------------------------------------
    | Dashboard Controller
    |--------------------------------------------------------------------------
    | The action invoked by the package's `/{prefix}` route. Host apps that
    | need a richer landing page (KPI tiles, charts, lists) override this
    | with their own controller — it must return Inertia::render('Dashboard',
    | [...]) so the package's Dashboard.vue page receives the props it
    | expects.
    |
    | Default: the package's bundled placeholder dashboard.
    */
    'dashboard_controller' => [DashboardController::class, 'index'],

    /*
    |--------------------------------------------------------------------------
    | Admin User Controller
    |--------------------------------------------------------------------------
    | The controller backing the Admin → Users screen. Host apps that need
    | extra fields on the user form (e.g. an "active company" selector) point
    | this at their own subclass of the package's controller. The subclass
    | inherits all CRUD behaviour and only fills in the host hooks
    | (`hostFormComponents()`, `hostValidationRules()`, `afterUserSaved()`...).
    |
    | Default: the package's bundled user controller.
    */
    'user_controller' => UserController::class,

    /*
    |--------------------------------------------------------------------------
    | Model map for DataController
    |--------------------------------------------------------------------------
    | Map URL slugs to fully qualified model class names.
    | App\Models are resolved automatically — only add custom namespaces here.
    */
    'models' => [],

    /*
    |--------------------------------------------------------------------------
    | Controller auto-routing
    |--------------------------------------------------------------------------
    | Map of absolute directory paths → PSR-4 namespaces that Darejer will
    | scan for controllers extending `Darejer\Http\Controllers\DarejerController`.
    | Matching controllers get their routes registered automatically, so
    | `routes/web.php` can stay empty for CRUD.
    |
    | Set to an empty array to disable auto-routing entirely.
    */
    'controllers' => [
        app_path('Http/Controllers') => 'App\\Http\\Controllers',
    ],

];
