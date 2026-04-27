<?php

use Illuminate\Support\Facades\Route;
use Darejer\Http\Controllers\AlertsController;
use Darejer\Http\Controllers\DashboardController;
use Darejer\Http\Controllers\DataController;

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

        // Dashboard — default landing after login. Host apps swap this
        // out via the `darejer.dashboard_controller` config to ship their
        // own KPI / chart payload to Dashboard.vue.
        Route::get('/', config('darejer.dashboard_controller', [DashboardController::class, 'index']))
            ->name('dashboard');

        // Data endpoint for Combobox, DataGrid, Kanban, TreeGrid etc.
        Route::get('/data/{model}', [DataController::class, 'index'])
            ->name('data.index');

        // Single-field update — used by Kanban drag+drop
        Route::patch('/data/{model}/{id}', [DataController::class, 'update'])
            ->name('data.update');

        // Per-user notifications. The list + count endpoints feed the
        // topbar Bell + slideover; live updates arrive over the
        // `darejer.alerts.{userId}` private broadcast channel.
        Route::prefix('alerts')->name('alerts.')->group(function () {
            Route::get('/', [AlertsController::class, 'index'])->name('index');
            Route::get('/count', [AlertsController::class, 'count'])->name('count');
            Route::post('/read-all', [AlertsController::class, 'markAllRead'])->name('read-all');
            Route::delete('/clear', [AlertsController::class, 'clear'])->name('clear');
            Route::post('/{id}/read', [AlertsController::class, 'markRead'])
                ->whereNumber('id')->name('read');
            Route::delete('/{id}', [AlertsController::class, 'destroy'])
                ->whereNumber('id')->name('destroy');
        });

    });
