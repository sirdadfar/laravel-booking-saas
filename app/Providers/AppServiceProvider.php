<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Services\TenantContext;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // One context per request/process. Octane resets it between requests
        // because the container is flushed, so a singleton is safe here.
        $this->app->singleton(TenantContext::class);
    }

    public function boot(): void
    {
        Notification::extend('sms', fn ($app) => $app->make(SmsChannel::class));

        // Login is the highest-value brute-force target. Key by a hash of the
        // submitted email plus the client IP: no plaintext PII in the cache
        // key, and one attacker cannot lock out a different account.
        RateLimiter::for('login', function (Request $request) {
            $email = Str::lower(trim((string) $request->input('email')));

            return Limit::perMinute(5)
                ->by(sha1($email).'|'.$request->ip())
                ->response(function (Request $request, array $headers) {
                    $message = 'تلاش‌های ورود بیش از حد مجاز است. یک دقیقه بعد دوباره تلاش کنید.';

                    if ($request->expectsJson()) {
                        return response()->json(['message' => $message], 429, $headers);
                    }

                    // A real 429 (not a 302 with a flash message) is what
                    // proxies, monitoring and the security smoke tests expect.
                    return response()->view('errors.429', [], 429)->withHeaders($headers);
                });
        });
    }
}
