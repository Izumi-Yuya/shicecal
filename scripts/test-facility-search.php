<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\Facility;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Facility Search Function Test ===\n\n";

// Test 1: Service type search
echo "1. Service type search test (デイサービス):\n";
$dayServiceFacilities = Facility::with('services')
    ->whereHas('services', function ($q) {
        $q->where('service_type', 'デイサービス');
    })
    ->take(5)
    ->get();

foreach ($dayServiceFacilities as $facility) {
    echo "  - {$facility->office_code}: {$facility->facility_name}\n";
}
echo '  Total: '.$dayServiceFacilities->count()." facilities\n\n";

// Test 2: Prefecture search (based on facility code)
echo "2. Prefecture search test (千葉県 - code 12):\n";
$chibaPrefectureFacilities = Facility::where('office_code', 'like', '12%')
    ->take(5)
    ->get();

foreach ($chibaPrefectureFacilities as $facility) {
    $prefectureCode = str_pad(substr($facility->office_code, 0, 2), 2, '0', STR_PAD_LEFT);
    $prefecture = config('prefectures.codes.'.$prefectureCode, '不明');
    echo "  - {$facility->office_code}: {$facility->facility_name} ({$prefecture})\n";
}
echo '  Total: '.$chibaPrefectureFacilities->count()." facilities\n\n";

// Test 3: Keyword search
echo "3. Keyword search test ('ラ・ナシカ'):\n";
$keywordFacilities = Facility::where(function ($q) {
    $keyword = 'ラ・ナシカ';
    $q->where('facility_name', 'like', "%{$keyword}%")
        ->orWhere('company_name', 'like', "%{$keyword}%");
})->take(5)->get();

foreach ($keywordFacilities as $facility) {
    echo "  - {$facility->office_code}: {$facility->facility_name} ({$facility->company_name})\n";
}
echo '  Total: '.$keywordFacilities->count()." facilities\n\n";

// Test 4: Available service types
echo "4. Available service types:\n";
$serviceTypes = DB::table('facility_services')
    ->select('service_type')
    ->distinct()
    ->orderBy('service_type')
    ->pluck('service_type');

foreach ($serviceTypes as $serviceType) {
    $count = DB::table('facility_services')->where('service_type', $serviceType)->count();
    echo "  - {$serviceType}: {$count} services\n";
}

// Test 5: Available prefectures (standard codes only)
echo "\n5. Available prefectures (standard 47 prefectures only):\n";
$prefectureCodes = Facility::select(DB::raw('DISTINCT SUBSTR(office_code, 1, 2) as prefecture_code'))
    ->orderBy('prefecture_code')
    ->pluck('prefecture_code');

foreach ($prefectureCodes as $code) {
    $paddedCode = str_pad($code, 2, '0', STR_PAD_LEFT);
    $codeNum = intval($paddedCode);

    if ($codeNum >= 1 && $codeNum <= 47) {
        $prefecture = config('prefectures.codes.'.$paddedCode, '未設定');
    } else {
        $prefecture = '未設定';
    }

    $count = Facility::where('office_code', 'like', $code.'%')->count();
    echo "  - {$prefecture} (コード: {$paddedCode}): {$count} facilities\n";
}

echo "\nTest completed!\n";
