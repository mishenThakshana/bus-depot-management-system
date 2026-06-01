<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Redirect root to login
Route::get('/', fn() => redirect()->route('login'));


// Auth (guests only) 
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.submit');

    Route::get('/forgot-password', fn() => view('auth.forgot-password'))
        ->name('password.request');

    Route::post('/forgot-password', fn() => back()->with('status', 'If that email exists, a reset link has been sent.'))
        ->name('password.email');
});

// Logout (authenticated users)
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Forced password change (shown on login page when must_change_password is true)
Route::middleware('auth')->group(function () {
    Route::get('/change-password', [AuthController::class, 'showChangePassword'])
        ->name('password.change');
    Route::post('/change-password', [AuthController::class, 'changePassword'])
        ->name('password.change.submit');
});


// Panel (authenticated users only) 
Route::prefix('panel')->name('panel.')->middleware(['auth', 'force.password.change'])->group(function () {

    Route::get('/', fn() => redirect()->route('panel.dashboard'));

    Route::get('/dashboard', fn() => view('panel.dashboard'))
        ->name('dashboard');

    // ── Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',                          [UserController::class, 'index'])->name('users');
        Route::post('/users',                         [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/toggle-status',   [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/audit-log', fn() => view('panel.audit-log'))->name('audit-log');
    });

    // ── Admin and Supervisor
    Route::get('/routes',    fn() => view('panel.routes'))->middleware('role:admin,supervisor')->name('routes');
    Route::get('/buses',     fn() => view('panel.buses'))->middleware('role:admin,supervisor')->name('buses');
    Route::get('/drivers',   fn() => view('panel.drivers'))->middleware('role:admin,supervisor')->name('drivers');
    Route::get('/schedules', fn() => view('panel.schedules'))->middleware('role:admin,supervisor')->name('schedules');

    // ── All roles
    Route::get('/trips',       fn() => view('panel.trips'))->name('trips');
    Route::get('/fuel',        fn() => view('panel.fuel'))->name('fuel');
    Route::get('/fuel-logs',   fn() => view('panel.fuel-logs'))->name('fuel-logs');
    Route::get('/maintenance', fn() => view('panel.maintenance'))->name('maintenance');
    Route::get('/reports',     fn() => view('panel.reports'))->name('reports');
});
