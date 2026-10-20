<?php

namespace App\Providers;

use App\Channels\SmsChannel;
use App\Services\TenantContext;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

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
    }
}
