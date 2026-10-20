<?php

use App\Exceptions\BookingRuleException;
use App\Exceptions\SlotUnavailableException;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\EnsureTenantAdmin;
use App\Http\Middleware\ResetTenantContext;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(ResetTenantContext::class);
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'tenant.admin' => EnsureTenantAdmin::class,
            'platform.admin' => EnsurePlatformAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (SlotUnavailableException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 'slot_unavailable',
                ], 409);
            }

            return back()->withErrors(['starts_at' => $e->getMessage()])->withInput();
        });

        $exceptions->render(function (BookingRuleException $e, Request $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                    'code' => 'booking_rule_violation',
                ], 422);
            }

            return back()->withErrors(['starts_at' => $e->getMessage()])->withInput();
        });
    })->create();
