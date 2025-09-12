<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FacilityMasterImportSeeder extends Seeder
{
    /**
     * サービスタイプとセクションのマッピング
     */
    private const SERVICE_SECTIONS = [
        'デイサービス' => '通所系サービス',
        '有料老人ホーム' => '入居系サービス',
        'グループホーム' => '認知症対応サービス',
        '訪問看護' => '在宅系サービス',
        'ヘルパー' => '在宅系サービス',
        'ケアプラン' => '在宅系サービス',
        '本社' => '管理部門',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = base_path('facility_master.csv');
        
        if (!file_exists($csvPath)) {
            $this->command->error('facility_master.csv file not found in project root.');
            return;
        }

        // Get admin user for created_by and updated_by
        $adminUser = User::where('role', 'admin')->first();
        $approverUser = User::where('role', 'approver')->first();
        
        if (!$adminUser) {
            $this->command->error('Admin user not found. Please run AdminUserSeeder first.');
            return;
        }

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $this->command->error('Could not open facility_master.csv file.');
            return;
        }

        // Skip header row
        fgetcsv($handle);
        
        $importedCount = 0;
        $skippedCount = 0;

        DB::beginTransaction();
        
        try {
            while (($data = fgetcsv($handle)) !== false) {
                [$facilityCode, $facilityName, $serviceType] = $data;
                
                // Skip rows with empty facility_code (like the visiting nurse station without code)
                if (empty($facilityCode)) {
                    $this->command->warn("Skipping facility without code: {$facilityName}");
                    $skippedCount++;
                    continue;
                }

                // Check if facility already exists
                if (Facility::where('office_code', $facilityCode)->exists()) {
                    $this->command->info("Facility {$facilityCode} already exists, skipping.");
                    $skippedCount++;
                    continue;
                }

                // Create facility
                $facility = Facility::create([
                    'company_name' => $this->getCompanyName($facilityName),
                    'office_code' => $facilityCode,
                    'designation_number' => $this->generateDesignationNumber($facilityCode),
                    'facility_name' => $facilityName,
                    'postal_code' => null, // Will be filled later
                    'address' => null, // Will be filled later
                    'phone_number' => null, // Will be filled later
                    'fax_number' => null, // Will be filled later
                    'status' => 'approved',
                    'approved_at' => now(),
                    'approved_by' => $approverUser?->id,
                    'created_by' => $adminUser->id,
                    'updated_by' => $adminUser->id,
                ]);

                // Create facility service
                DB::table('facility_services')->insert([
                    'facility_id' => $facility->id,
                    'service_type' => $serviceType,
                    'section' => $this->getServiceSection($serviceType),
                    'renewal_start_date' => '2024-04-01',
                    'renewal_end_date' => '2030-03-31',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $importedCount++;
                $this->command->info("Imported: {$facilityCode} - {$facilityName}");
            }

            DB::commit();
            
            $this->command->info("Import completed successfully!");
            $this->command->info("Imported: {$importedCount} facilities");
            $this->command->info("Skipped: {$skippedCount} facilities");
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error("Import failed: " . $e->getMessage());
            Log::error('Facility import failed', ['error' => $e->getMessage()]);
        } finally {
            fclose($handle);
        }
    }

    /**
     * 施設名に基づいて会社名を判定
     */
    private function getCompanyName(string $facilityName): string
    {
        // 株式会社パイン系施設の判定
        if (str_contains($facilityName, '麻生の郷') ||
            str_contains($facilityName, '武蔵野の郷') ||
            str_contains($facilityName, 'わらび 花の郷') ||
            str_contains($facilityName, '靎見の鄕') ||
            str_contains($facilityName, '小文字の郷') ||
            str_contains($facilityName, 'わじろの郷')) {
            return '株式会社パイン';
        }
        
        // それ以外はすべて株式会社シダー
        return '株式会社シダー';
    }

    /**
     * サービスタイプに基づいてセクションを取得
     */
    private function getServiceSection(string $serviceType): string
    {
        return self::SERVICE_SECTIONS[$serviceType] ?? '在宅系サービス';
    }

    /**
     * 施設コードに基づいて指定番号を生成
     */
    private function generateDesignationNumber(string $facilityCode): string
    {
        // 施設コードの最初の2桁を都道府県コードとして使用
        $prefectureCode = substr($facilityCode, 0, 2);
        $facilityNumber = substr($facilityCode, 2);
        
        return $prefectureCode . '71200' . str_pad($facilityNumber, 3, '0', STR_PAD_LEFT);
    }
}