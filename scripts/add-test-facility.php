<?php

require_once __DIR__.'/../vendor/autoload.php';

use App\Models\Facility;
use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Adding Test Facility (麻生の郷) ===\n\n";

$adminUser = User::where('role', 'admin')->first();
if (! $adminUser) {
    echo "Admin user not found!\n";
    exit(1);
}

// 麻生の郷の施設を作成
$facility = Facility::create([
    'company_name' => '株式会社パイン',
    'office_code' => 'TEST001',
    'designation_number' => '1371200999',
    'facility_name' => '麻生の郷デイサービスセンター',
    'postal_code' => '100-0001',
    'address' => '東京都千代田区麻生1-2-3',
    'phone_number' => '03-1234-5678',
    'fax_number' => '03-1234-5679',
    'status' => 'approved',
    'approved_at' => now(),
    'approved_by' => $adminUser->id,
    'created_by' => $adminUser->id,
    'updated_by' => $adminUser->id,
]);

// サービス情報も追加
DB::table('facility_services')->insert([
    'facility_id' => $facility->id,
    'service_type' => 'デイサービス',
    'section' => '通所系サービス',
    'renewal_start_date' => '2024-04-01',
    'renewal_end_date' => '2030-03-31',
    'created_at' => now(),
    'updated_at' => now(),
]);

echo "Test facility added successfully:\n";
echo "  - Code: {$facility->office_code}\n";
echo "  - Name: {$facility->facility_name}\n";
echo "  - Company: {$facility->company_name}\n";

// 検索テスト
echo "\nTesting keyword search:\n";
$searchResults = Facility::where(function ($q) {
    $keyword = '麻生の郷';
    $q->where('facility_name', 'like', "%{$keyword}%")
        ->orWhere('company_name', 'like', "%{$keyword}%")
        ->orWhere('office_code', 'like', "%{$keyword}%")
        ->orWhere('address', 'like', "%{$keyword}%");
})->get();

echo "Search results for '麻生の郷': {$searchResults->count()} facilities found\n";
foreach ($searchResults as $result) {
    echo "  - {$result->office_code}: {$result->facility_name} ({$result->company_name})\n";
}

echo "\nTest completed!\n";
