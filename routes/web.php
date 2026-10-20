<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Platform\TenantController;
use App\Http\Controllers\Public\PublicBookingController;
use Illuminate\Support\Facades\Route;

/*
 * The home page doubles as the public booking page when the request comes
 * through a tenant subdomain (demo.localhost) or the X-Tenant header.
 */
Route::get('/', HomeController::class)->middleware('tenant')->name('home');

Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'store'])
    ->middleware('throttle:login')
    ->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->middleware('auth')->name('logout');

/*
 * Platform administration: list of tenants. Not tenant-scoped.
 */
Route::prefix('platform')
    ->name('platform.')
    ->middleware(['auth', 'platform.admin'])
    ->group(function (): void {
        Route::get('/tenants', [TenantController::class, 'index'])->name('tenants');
    });

/*
 * Tenant administration. Every route resolves the tenant from the path first,
 * so a tenant admin can never reach another tenant's dashboard.
 */
Route::prefix('{tenant}/admin')
    ->name('admin.')
    ->middleware(['tenant', 'auth', 'tenant.admin'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/bookings', [AdminBookingController::class, 'index'])->name('bookings');
        Route::post('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])->name('bookings.status');

        Route::get('/services', [ServiceController::class, 'index'])->name('services');
        Route::post('/services', [ServiceController::class, 'store'])->name('services.store');
        Route::put('/services/{service}', [ServiceController::class, 'update'])->name('services.update');
        Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->name('services.destroy');

        Route::get('/staff', [StaffController::class, 'index'])->name('staff');
        Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
        Route::put('/staff/{staff}', [StaffController::class, 'update'])->name('staff.update');
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy'])->name('staff.destroy');
    });

/*
 * Public booking flow.
 */
Route::prefix('{tenant}')
    ->name('public.')
    ->middleware('tenant')
    ->group(function (): void {
        Route::get('/', [PublicBookingController::class, 'show'])->name('booking');
        Route::get('/slots', [PublicBookingController::class, 'slots'])
            ->middleware('throttle:60,1')
            ->name('slots');
        Route::post('/bookings', [PublicBookingController::class, 'store'])
            ->middleware('throttle:10,1')
            ->name('bookings.store');
        Route::get('/bookings/{code}', [PublicBookingController::class, 'confirmation'])
            ->middleware('throttle:60,1')
            ->name('confirmation');
        // The booking code is the only credential on this route; cap guesses.
        Route::post('/bookings/{code}/cancel', [PublicBookingController::class, 'cancel'])
            ->middleware('throttle:10,1')
            ->name('cancel');
    });
