<?php

namespace App\Providers;

use App\Services\Telemetry\TracerService;
use Illuminate\Support\ServiceProvider;
use OpenTelemetry\API\Trace\TracerProviderInterface;
use OpenTelemetry\API\Globals;

class TelemetryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(TracerProviderInterface::class, function ($app) {
            return Globals::tracerProvider();
        });

        $this->app->singleton(TracerService::class, function ($app) {
            return new TracerService($app->make(TracerProviderInterface::class));
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
