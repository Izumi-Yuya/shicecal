<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\FacilityService;
use App\Services\ExportService;
use App\Services\NotificationService;
use App\Services\ActivityLogService;
use App\Services\PerformanceMonitoringService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Register consolidated services
        $this->app->singleton(FacilityService::class);
        $this->app->singleton(ExportService::class);
        $this->app->singleton(NotificationService::class);
        $this->app->singleton(ActivityLogService::class);
        $this->app->singleton(PerformanceMonitoringService::class);
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
