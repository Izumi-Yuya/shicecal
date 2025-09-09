<?php

namespace App\Providers;

use App\Http\View\Composers\ServiceTableComposer;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

/**
 * Service Provider for View Composers
 * Registers view composers for better separation of concerns
 */
class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register services
     */
    public function register(): void
    {
        //
    }
    
    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Register Service Table View Composer
        View::composer(
            [
                'facilities.partials.service-table',
                'facilities.partials.service-table-improved'
            ],
            ServiceTableComposer::class
        );
    }
}