<?php

namespace App\Console\Commands;

use App\Models\FacilityInfo;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportFacilityMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facility:import-master {file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import facility master data from CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (! file_exists($filePath)) {
            $this->error("File not found: {$filePath}");

            return 1;
        }

        // Get the first admin user for created_by and updated_by
        $adminUser = User::where('role', 'admin')->first();
        if (! $adminUser) {
            $this->error('No admin user found. Please create an admin user first.');

            return 1;
        }

        $this->info("Starting import from: {$filePath}");
        $this->info("Using admin user ID: {$adminUser->id} ({$adminUser->name})");

        $handle = fopen($filePath, 'r');
        if (! $handle) {
            $this->error("Could not open file: {$filePath}");

            return 1;
        }

        // Skip header row
        $header = fgetcsv($handle);
        $this->info('CSV Header: '.implode(', ', $header));

        $imported = 0;
        $skipped = 0;
        $errors = 0;

        DB::beginTransaction();

        try {
            while (($row = fgetcsv($handle)) !== false) {
                if (count($row) < 3) {
                    $this->warn('Skipping incomplete row: '.implode(', ', $row));
                    $skipped++;

                    continue;
                }

                [$facilityCode, $facilityName, $section] = $row;

                // Skip if facility_code is empty
                if (empty($facilityCode)) {
                    $this->warn("Skipping row with empty facility_code: {$facilityName}");
                    $skipped++;

                    continue;
                }

                // Check if facility already exists
                $existingFacility = FacilityInfo::where('office_code', $facilityCode)->first();
                if ($existingFacility) {
                    $this->warn("Facility already exists with code {$facilityCode}: {$facilityName}");
                    $skipped++;

                    continue;
                }

                try {
                    // Determine company name based on facility name
                    $companyName = $this->determineCompanyName($facilityName);

                    // Create facility info
                    FacilityInfo::create([
                        'company_name' => $companyName,
                        'office_code' => $facilityCode,
                        'designation_number' => null, // Will be set later if needed
                        'facility_name' => $facilityName,
                        'postal_code' => null,
                        'address' => null,
                        'building_name' => null,
                        'phone_number' => null,
                        'fax_number' => null,
                        'toll_free_number' => null,
                        'email' => null,
                        'website_url' => null,
                        'status' => 'draft', // Start as draft
                        'approved_at' => null,
                        'approved_by' => null,
                        'created_by' => $adminUser->id,
                        'updated_by' => $adminUser->id,
                    ]);

                    $imported++;
                    $this->info("Imported: {$facilityCode} - {$facilityName}");

                } catch (\Exception $e) {
                    $this->error("Error importing {$facilityCode} - {$facilityName}: ".$e->getMessage());
                    $errors++;
                }
            }

            DB::commit();
            fclose($handle);

            $this->info("\n=== Import Summary ===");
            $this->info("Imported: {$imported}");
            $this->info("Skipped: {$skipped}");
            $this->info("Errors: {$errors}");
            $this->info('Total processed: '.($imported + $skipped + $errors));

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            fclose($handle);
            $this->error('Import failed: '.$e->getMessage());

            return 1;
        }
    }

    /**
     * Determine company name based on facility name
     */
    private function determineCompanyName(string $facilityName): string
    {
        if (strpos($facilityName, 'ラ・ナシカ') !== false) {
            return '株式会社シダー';
        }

        if (strpos($facilityName, 'あおぞらの里') !== false) {
            return '株式会社シダー';
        }

        if (strpos($facilityName, '本社') !== false || strpos($facilityName, '本部') !== false) {
            return '株式会社シダー';
        }

        // Default company name
        return '株式会社シダー';
    }
}
