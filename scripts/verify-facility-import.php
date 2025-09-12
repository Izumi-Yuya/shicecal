<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Facility;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Facility Import Verification ===\n\n";

// Count total facilities
$totalFacilities = Facility::count();
echo "Total facilities in database: {$totalFacilities}\n\n";

// Count by service type
echo "Facilities by service type:\n";
$serviceTypeCounts = DB::table('facility_services')
    ->select('service_type', DB::raw('count(*) as count'))
    ->groupBy('service_type')
    ->orderBy('count', 'desc')
    ->get();

foreach ($serviceTypeCounts as $service) {
    echo "  {$service->service_type}: {$service->count}\n";
}

echo "\n";

// Count by company
echo "Facilities by company:\n";
$companyCounts = Facility::select('company_name', DB::raw('count(*) as count'))
    ->groupBy('company_name')
    ->orderBy('count', 'desc')
    ->get();

foreach ($companyCounts as $company) {
    echo "  {$company->company_name}: {$company->count}\n";
}

echo "\nCompany name assignment verification:\n";
$companyExamples = [
    '社会福祉法人あおぞらの里' => Facility::where('company_name', '社会福祉法人あおぞらの里')->take(2)->pluck('facility_name'),
    '株式会社ラ・ナシカ' => Facility::where('company_name', '株式会社ラ・ナシカ')->take(2)->pluck('facility_name'),
    '株式会社シダー' => Facility::where('company_name', '株式会社シダー')->take(2)->pluck('facility_name'),
    '株式会社パイン' => Facility::where('company_name', '株式会社パイン')->take(2)->pluck('facility_name'),
];

foreach ($companyExamples as $company => $examples) {
    if ($examples->isNotEmpty()) {
        echo "  {$company}:\n";
        foreach ($examples as $example) {
            echo "    - {$example}\n";
        }
    }
}

echo "\n";

// Show sample facilities from CSV import
echo "Sample imported facilities:\n";
$sampleFacilities = Facility::whereIn('office_code', ['10901', '11201', '20101', '21201', '31301'])
    ->with('services')
    ->get();

foreach ($sampleFacilities as $facility) {
    $services = $facility->services->pluck('service_type')->join(', ');
    echo "  {$facility->office_code} - {$facility->facility_name}\n";
    echo "    Company: {$facility->company_name}\n";
    echo "    Services: {$services}\n\n";
}

echo "Verification completed!\n";