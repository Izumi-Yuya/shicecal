<?php

namespace App\Console\Commands;

use App\Models\FacilityInfo;
use App\Models\FacilityBasic;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFacilityBasicFromMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facility:import-basic-from-master {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import facility basic data (section info) from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        // Get the first admin user for created_by and updated_by
        $adminUser = User::where('role', 'admin')->first();
        if (!$adminUser) {
            $this->error('No admin user found. Please create an admin user first.');
            return 1;
        }

        $this->info("Starting basic info import from: {$filePath}");
        $this->info("Using admin user ID: {$adminUser->id} ({$adminUser->name})");

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return 1;
        }

        // Skip header row
        $header = fgetcsv($handle);

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) {
                    $skipped++;
                    continue;
                }

                [$facilityCode, $facilityName, $section] = $row;

                // Skip if facility_code is empty
                if (empty($facilityCode)) {
                    $skipped++;
                    continue;
                }

                // Find the corresponding facility info
                $facilityInfo = FacilityInfo::where('office_code', $facilityCode)->first();
                if (!$facilityInfo) {
                    $this->warn("Facility not found with code {$facilityCode}: {$facilityName}");
                    $skipped++;
                    continue;
                }

                // Check if basic info already exists
                $existingBasic = FacilityBasic::where('facility_id', $facilityInfo->id)->first();
                if ($existingBasic) {
                    $this->warn("Basic info already exists for facility {$facilityCode}: {$facilityName}");
                    $skipped++;
                    continue;
                }

                try {
                    // Map section to standardized values
                    $standardizedSection = $this->mapSectionName($section);

                    // Create facility basic info
                    FacilityBasic::create([
                        'facility_id' => $facilityInfo->id,
                        'opening_date' => null,
                        'years_in_operation' => null,
                        'designation_renewal_date' => null,
                        'building_structure' => null,
                        'building_floors' => null,
                        'paid_rooms_count' => null,
                        'ss_rooms_count' => null,
                        'capacity' => null,
                        'service_types' => null,
                        'section' => $standardizedSection,
                        'status' => 'draft',
                        'approved_at' => null,
                        'approved_by' => null,
                        'created_by' => $adminUser->id,
                        'updated_by' => $adminUser->id,
                    ]);

                    $imported++;
                    $this->info("Created basic info for: {$facilityCode} - {$facilityName} (Section: {$standardizedSection})");

                } catch (\Exception $e) {
                    $this->error("Error creating basic info for {$facilityCode} - {$facilityName}: " . $e->getMessage());
                    $errors++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("\n=== Basic Info Import Summary ===");
            $this->info("Imported: {$imported}");
            $this->info("Skipped: {$skipped}");
            $this->info("Errors: {$errors}");
            $this->info("Total processed: " . ($imported + $skipped + $errors));

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error("Import failed: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Map section names to standardized values
     */
    private function mapSectionName(string $section): string
    {
        $sectionMap = [
            'デイサービス' => 'デイサービスセンター',
            '有料老人ホーム' => '有料老人ホーム',
            'グループホーム' => 'グループホーム',
            '訪問看護' => '訪問看護ステーション',
            'ヘルパー' => 'ヘルパーステーション',
            'ケアプラン' => 'ケアプランセンター',
            '本社' => '他（事務所など）',
        ];

        return $sectionMap[$section] ?? $section;
    }
}