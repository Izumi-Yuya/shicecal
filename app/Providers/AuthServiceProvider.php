<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        \App\Models\LandInfo::class => \App\Policies\LandInfoPolicy::class,
        \App\Models\LifelineEquipment::class => \App\Policies\LifelineEquipmentPolicy::class,
        \App\Models\FacilityContract::class => \App\Policies\ContractPolicy::class,
        \App\Models\DocumentFile::class => \App\Policies\DocumentPolicy::class,
        \App\Models\DocumentFolder::class => \App\Policies\DocumentPolicy::class,
        \App\Models\MaintenanceHistory::class => \App\Policies\MaintenanceHistoryPolicy::class,
        \App\Models\FacilityDrawing::class => \App\Policies\DrawingPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
