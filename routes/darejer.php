<?php

use Darejer\Http\Controllers\AlertsController;
use Darejer\Http\Controllers\DashboardController;
use Darejer\Http\Controllers\DataController;
use Darejer\Http\Controllers\HomeController;
use Darejer\Http\Controllers\LocaleController;
use Darejer\Http\Controllers\ProfileController;
use Darejer\Http\Controllers\SearchController;
use Illuminate\Support\Facades\Route;

// ── Locale switching ────────────────────────────────────────────────────────
//
// Lives outside the auth group so guests on login / forgot-password screens
// can switch language too. POST + back() preserves the caller's URL and its
// query string — the topbar switcher relies on that to keep filter / paging
// state intact across a locale change.
Route::prefix(config('darejer.route_prefix', 'darejer'))
    ->middleware(['web'])
    ->name('darejer.')
    ->group(function () {
        Route::post('/locale', [LocaleController::class, 'update'])->name('locale.update');
    });

// ── Authenticated routes ────────────────────────────────────────────────────
//
// Auth endpoints (login, logout, forgot/reset password, 2FA challenge, email
// verification, confirm password) are registered by Laravel Fortify under the
// same prefix as Darejer — see `config/fortify.php`. The routes below are
// Darejer's own authenticated surface.
Route::prefix(config('darejer.route_prefix', 'darejer'))
    ->middleware(config('darejer.middleware', ['web', 'auth']))
    ->name('darejer.')
    ->group(function () {

        // Home — default landing after login. Renders an ERPNext-style
        // module index that mirrors the NavigationManager tree as a tile
        // grid, so the home view itself is the navigation hub.
        Route::get('/', [HomeController::class, 'index'])->name('home');

        // Dashboard — KPI / chart view, now a regular nav entry rather
        // than the landing page. Host apps swap the implementation via
        // the `darejer.dashboard_controller` config and the bundled
        // Dashboard.vue receives whatever props they ship.
        Route::get('/dashboard', config('darejer.dashboard_controller', [DashboardController::class, 'index']))
            ->name('dashboard');

        // Data endpoint for Combobox, DataGrid, Kanban, TreeGrid etc.
        Route::get('/data/{model}', [DataController::class, 'index'])
            ->name('data.index');

        // Single-field update — used by Kanban drag+drop
        Route::patch('/data/{model}/{id}', [DataController::class, 'update'])
            ->name('data.update');

        // Global search — feeds the topbar quick-jump. Walks every
        // ModelRegistry entry that uses the Searchable trait.
        Route::get('/search', [SearchController::class, 'index'])->name('search');

        // Self-service profile editor — linked from the topbar user
        // dropdown. Persists via Fortify's UpdatesUserProfileInformation
        // / UpdatesUserPasswords contracts.
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Per-user notifications. The list + count endpoints feed the
        // topbar Bell + slideover; live updates arrive over the
        // `darejer.alerts.{userId}` private broadcast channel.
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/', [AlertsController::class, 'index'])->name('index');
            Route::get('/count', [AlertsController::class, 'count'])->name('count');
            Route::post('/read-all', [AlertsController::class, 'markAllRead'])->name('read_all');
            Route::delete('/clear', [AlertsController::class, 'clear'])->name('clear');
            Route::post('/{id}/read', [AlertsController::class, 'markRead'])
                ->whereNumber('id')->name('read');
            Route::delete('/{id}', [AlertsController::class, 'destroy'])
                ->whereNumber('id')->name('destroy');
        });

    });
