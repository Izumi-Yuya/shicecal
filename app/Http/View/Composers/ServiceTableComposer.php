<?php

namespace App\Http\View\Composers;

use App\Services\ServiceTable\ServiceTableViewHelper;
use App\Services\ServiceTable\ServiceDataSanitizer;
use Illuminate\View\View;

/**
 * View Composer for Service Table
 * Implements the View Composer pattern to separate data preparation from presentation
 */
class ServiceTableComposer
{
    private ServiceTableViewHelper $viewHelper;
    private ServiceDataSanitizer $sanitizer;
    
    public function __construct(
        ServiceTableViewHelper $viewHelper,
        ServiceDataSanitizer $sanitizer
    ) {
        $this->viewHelper = $viewHelper;
        $this->sanitizer = $sanitizer;
    }
    
    /**
     * Bind data to the view
     */
    public function compose(View $view): void
    {
        $services = $view->getData()['services'] ?? collect();
        
        // Sanitize service data
        $sanitizedServices = $services->map(function ($service) {
            return $this->sanitizer->sanitizeServiceData($service);
        });
        
        // Prepare table data
        $tableData = $this->viewHelper->prepareServiceTableData($sanitizedServices);
        
        // Get configuration
        $config = config('service-table');
        
        // Bind processed data to view
        $view->with([
            'tableData' => $tableData,
            'sanitizedServices' => $sanitizedServices,
            'serviceTableConfig' => $config,
            'viewHelper' => $this->viewHelper,
        ]);
    }
}