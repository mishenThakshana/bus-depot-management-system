<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to login
Route::get('/', fn () => redirect()->route('login'));


// ── Auth ──────────────────────────────────────────────────────────────────

Route::get('/login', fn () => view('auth.login'))
    ->name('login');

Route::post('/login', fn () => redirect()->route('panel.dashboard'))
    ->name('login.submit');

Route::get('/forgot-password', fn () => view('auth.forgot-password'))
    ->name('password.request');

Route::post('/forgot-password', fn () => back()->with('status', 'If that email exists, a reset link has been sent.'))
    ->name('password.email');


// ── Panel ─────────────────────────────────────────────────────────────────

Route::prefix('panel')->name('panel.')->group(function () {

    Route::get('/', fn () => redirect()->route('panel.dashboard'));

    Route::get('/dashboard', fn () => view('panel.dashboard'))
        ->name('dashboard');

    // Placeholder routes for sidebar items (return dashboard view until pages are built)
    Route::get('/users',        fn () => view('panel.dashboard'))->name('users');
    Route::get('/routes',       fn () => view('panel.dashboard'))->name('routes');
    Route::get('/buses',        fn () => view('panel.dashboard'))->name('buses');
    Route::get('/drivers',      fn () => view('panel.dashboard'))->name('drivers');
    Route::get('/schedules',    fn () => view('panel.dashboard'))->name('schedules');
    Route::get('/trips',        fn () => view('panel.dashboard'))->name('trips');
    Route::get('/fuel',         fn () => view('panel.dashboard'))->name('fuel');
    Route::get('/reports',      fn () => view('panel.dashboard'))->name('reports');
    Route::get('/audit-log',    fn () => view('panel.dashboard'))->name('audit-log');
    Route::get('/fuel-logs',    fn () => view('panel.dashboard'))->name('fuel-logs');
    Route::get('/maintenance',  fn () => view('panel.dashboard'))->name('maintenance');

});
