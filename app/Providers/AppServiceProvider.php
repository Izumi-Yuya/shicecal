<?php

namespace App\Providers;

use App\Services\ActivityLogService;
use App\Services\ExportService;
use App\Services\FacilityService;
use App\Services\NotificationService;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\ServiceProvider;

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
        // Register view composers
        $this->registerViewComposers();
    }

    /**
     * Register view composers
     */
    private function registerViewComposers(): void
    {
        view()->composer(
            'facilities.services.partials.table',
            \App\Http\View\Composers\ServiceTableComposer::class
        );
    }
}
