<?php

use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\BusController;
use App\Http\Controllers\Admin\DriverController;
use App\Http\Controllers\Admin\FuelController;
use App\Http\Controllers\Admin\MaintenanceController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\ScheduleRunController;
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

    Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])
        ->name('password.email');

    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])
        ->name('password.reset');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->name('password.update');
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
Route::prefix('panel')->name('panel.')->middleware(['auth', 'force.password.change', 'session.timeout'])->group(function () {

    Route::get('/', fn() => redirect()->route('panel.dashboard'));

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // ── Admin-only
    Route::middleware('role:admin')->group(function () {
        Route::get('/users',                          [UserController::class, 'index'])->name('users');
        Route::post('/users',                         [UserController::class, 'store'])->name('users.store');
        Route::patch('/users/{user}/toggle-status',   [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log');
    });

    // ── Admin and Supervisor
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/routes',           [RouteController::class, 'index'])->name('routes');
        Route::post('/routes',          [RouteController::class, 'store'])->name('routes.store');
        Route::patch('/routes/{route}', [RouteController::class, 'update'])->name('routes.update');
        Route::delete('/routes/{route}',[RouteController::class, 'destroy'])->name('routes.destroy');
    });
    // ── Admin and Supervisor (buses)
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/buses',          [BusController::class, 'index'])->name('buses');
        Route::post('/buses',         [BusController::class, 'store'])->name('buses.store');
        Route::patch('/buses/{bus}',  [BusController::class, 'update'])->name('buses.update');
        Route::delete('/buses/{bus}', [BusController::class, 'destroy'])->name('buses.destroy');
    });
    // ── Admin and Supervisor (drivers)
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/drivers',                            [DriverController::class, 'index'])->name('drivers');
        Route::post('/drivers',                           [DriverController::class, 'store'])->name('drivers.store');
        Route::patch('/drivers/{driver}',                 [DriverController::class, 'update'])->name('drivers.update');
        Route::patch('/drivers/{driver}/toggle-active',   [DriverController::class, 'toggleActive'])->name('drivers.toggle-active');
    });
    // ── Admin and Supervisor (schedules)
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/schedules',              [ScheduleController::class, 'index'])->name('schedules');
        Route::post('/schedules',             [ScheduleController::class, 'store'])->name('schedules.store');
        Route::patch('/schedules/{schedule}', [ScheduleController::class, 'update'])->name('schedules.update');

        Route::get('/schedules/{schedule}/runs',                    [ScheduleRunController::class, 'index'])->name('schedules.runs');
        Route::patch('/schedules/{schedule}/runs/{run}',            [ScheduleRunController::class, 'reschedule'])->name('schedules.runs.reschedule');
        Route::patch('/schedules/{schedule}/runs/{run}/cancel',     [ScheduleRunController::class, 'cancel'])->name('schedules.runs.cancel');
        Route::patch('/schedules/{schedule}/runs/{run}/reactivate', [ScheduleRunController::class, 'reactivate'])->name('schedules.runs.reactivate');
    });

    // ── Fuel & Maintenance — Admin/Supervisor (full CRUD)
    Route::middleware('role:admin,supervisor')->group(function () {
        Route::get('/fuel',                               [FuelController::class, 'index'])->name('fuel');
        Route::post('/fuel',                              [FuelController::class, 'store'])->name('fuel.store');
        Route::patch('/fuel/{fuelLog}',                   [FuelController::class, 'update'])->name('fuel.update');
        Route::delete('/fuel/{fuelLog}',                  [FuelController::class, 'destroy'])->name('fuel.destroy');

        Route::post('/maintenance',                            [MaintenanceController::class, 'store'])->name('maintenance.store');
        Route::patch('/maintenance/{maintenanceRecord}',       [MaintenanceController::class, 'update'])->name('maintenance.update');
        Route::delete('/maintenance/{maintenanceRecord}',      [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');
    });

    // ── Fuel & Maintenance — Staff (read-only views)
    Route::get('/fuel-logs',   [FuelController::class, 'staffFuelLogs'])->name('fuel-logs');
    Route::get('/maintenance', [MaintenanceController::class, 'staffIndex'])->name('maintenance');

    Route::get('/reports',                    [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/fuel',               [ReportController::class, 'exportFuel'])->name('reports.fuel');
    Route::get('/reports/maintenance',        [ReportController::class, 'exportMaintenance'])->name('reports.maintenance');
    Route::get('/reports/schedule',           [ReportController::class, 'exportSchedule'])->name('reports.schedule');
});
