<?php

namespace App\Providers;


use Illuminate\Support\Facades\Blade;
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


        // Register Facility Form Blade Directives
        $this->registerFacilityFormDirectives();
    }

    /**
     * Register custom Blade directives for facility forms
     */
    protected function registerFacilityFormDirectives(): void
    {
        // Directive for generating breadcrumbs
        Blade::directive('facilityBreadcrumbs', function ($expression) {
            return "<?php echo view('components.facility.breadcrumbs', ['breadcrumbs' => App\Helpers\FacilityFormHelper::generateBreadcrumbs{$expression}])->render(); ?>";
        });

        // Directive for form section with automatic icon and color
        Blade::directive('facilitySection', function ($expression) {
            return "<?php 
                \$sectionConfig = App\Helpers\FacilityFormHelper::getSectionConfig{$expression};
                echo view('components.form.section', \$sectionConfig)->render();
            ?>";
        });

        // Directive for facility info card
        Blade::directive('facilityInfoCard', function ($expression) {
            return "<?php echo view('components.facility.info-card', ['facility' => {$expression}])->render(); ?>";
        });

        // Directive for form actions with default routes
        Blade::directive('facilityFormActions', function ($expression) {
            return "<?php echo view('components.form.actions', {$expression})->render(); ?>";
        });

        // Directive to get section icon
        Blade::directive('sectionIcon', function ($expression) {
            return "<?php echo App\Helpers\FacilityFormHelper::getSectionIcon({$expression}); ?>";
        });

        // Directive to get section color
        Blade::directive('sectionColor', function ($expression) {
            return "<?php echo App\Helpers\FacilityFormHelper::getSectionColor({$expression}); ?>";
        });
    }
}
