<?php

namespace App\Console\Commands;

use App\Models\FacilityInfo;
use App\Models\FacilityBasic;
use App\Models\FacilityService;
use App\Models\LandInfo;
use App\Models\BuildingInfo;
use App\Models\LifelineEquipment;
use App\Models\FacilityComment;
use App\Models\MaintenanceHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupSampleData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facility:cleanup-sample-data {--dry-run : Show what would be deleted without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete sample data that is not from the facility master CSV';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('DRY RUN MODE - No data will be deleted');
        } else {
            $this->warn('This will permanently delete sample data!');
            if (!$this->confirm('Are you sure you want to continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        // CSVから読み込んだ施設コードのリストを取得
        $csvFacilityCodes = $this->getCsvFacilityCodes();
        $this->info('CSV facility codes count: ' . count($csvFacilityCodes));

        DB::beginTransaction();

        try {
            // CSVに含まれない施設を特定
            $facilitiesToDelete = FacilityInfo::whereNotIn('office_code', $csvFacilityCodes)->get();
            
            $this->info("\n=== Facilities to be deleted ===");
            $deleteCount = 0;
            
            foreach ($facilitiesToDelete as $facility) {
                $this->line("ID: {$facility->id}, Code: {$facility->office_code}, Name: {$facility->facility_name}");
                
                if (!$dryRun) {
                    // 関連データを削除
                    $this->deleteRelatedData($facility->id);
                    
                    // 施設本体を削除
                    $facility->delete();
                }
                
                $deleteCount++;
            }

            // 孤立したデータのクリーンアップ
            $this->cleanupOrphanedData($dryRun);

            if (!$dryRun) {
                DB::commit();
                $this->info("\n✅ Successfully deleted {$deleteCount} sample facilities and their related data.");
            } else {
                DB::rollBack();
                $this->info("\n📋 DRY RUN: Would delete {$deleteCount} sample facilities and their related data.");
            }

            // 最終状態を表示
            $this->showFinalStats();

            return 0;

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error during cleanup: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get facility codes from CSV file
     */
    private function getCsvFacilityCodes(): array
    {
        $csvPath = base_path('facility_master.csv');
        
        if (!file_exists($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return [];
        }

        $codes = [];
        $handle = fopen($csvPath, 'r');
        
        // Skip header
        fgetcsv($handle);
        
        while (($row = fgetcsv($handle)) !== false) {
            if (!empty($row[0])) { // facility_code
                $codes[] = $row[0];
            }
        }
        
        fclose($handle);
        return $codes;
    }

    /**
     * Delete related data for a facility
     */
    private function deleteRelatedData(int $facilityId): void
    {
        // FacilityBasic
        FacilityBasic::where('facility_id', $facilityId)->delete();
        
        // FacilityService
        FacilityService::where('facility_id', $facilityId)->delete();
        
        // LandInfo
        LandInfo::where('facility_id', $facilityId)->delete();
        
        // BuildingInfo
        BuildingInfo::where('facility_id', $facilityId)->delete();
        
        // LifelineEquipment and related equipment
        $lifelineEquipments = LifelineEquipment::where('facility_id', $facilityId)->get();
        foreach ($lifelineEquipments as $equipment) {
            // Delete related equipment based on category
            switch ($equipment->category) {
                case 'electrical':
                    $equipment->electricalEquipment?->delete();
                    break;
                case 'gas':
                    $equipment->gasEquipment?->delete();
                    break;
                case 'water':
                    $equipment->waterEquipment?->delete();
                    break;
                case 'elevator':
                    $equipment->elevatorEquipment?->delete();
                    break;
                case 'hvac_lighting':
                    $equipment->hvacLightingEquipment?->delete();
                    break;
            }
            $equipment->delete();
        }
        
        // FacilityComment
        FacilityComment::where('facility_id', $facilityId)->delete();
        
        // MaintenanceHistory
        MaintenanceHistory::where('facility_id', $facilityId)->delete();
        
        // Files (if any)
        DB::table('files')->where('facility_id', $facilityId)->delete();
    }

    /**
     * Clean up orphaned data
     */
    private function cleanupOrphanedData(bool $dryRun): void
    {
        $this->info("\n=== Cleaning up orphaned data ===");
        
        // Get valid facility IDs
        $validFacilityIds = FacilityInfo::pluck('id')->toArray();
        
        // Clean up orphaned FacilityBasic records
        $orphanedBasic = FacilityBasic::whereNotIn('facility_id', $validFacilityIds)->count();
        if ($orphanedBasic > 0) {
            $this->line("Orphaned FacilityBasic records: {$orphanedBasic}");
            if (!$dryRun) {
                FacilityBasic::whereNotIn('facility_id', $validFacilityIds)->delete();
            }
        }
        
        // Clean up orphaned FacilityService records
        $orphanedServices = FacilityService::whereNotIn('facility_id', $validFacilityIds)->count();
        if ($orphanedServices > 0) {
            $this->line("Orphaned FacilityService records: {$orphanedServices}");
            if (!$dryRun) {
                FacilityService::whereNotIn('facility_id', $validFacilityIds)->delete();
            }
        }
        
        // Clean up orphaned LandInfo records
        $orphanedLand = LandInfo::whereNotIn('facility_id', $validFacilityIds)->count();
        if ($orphanedLand > 0) {
            $this->line("Orphaned LandInfo records: {$orphanedLand}");
            if (!$dryRun) {
                LandInfo::whereNotIn('facility_id', $validFacilityIds)->delete();
            }
        }
        
        // Clean up orphaned LifelineEquipment records
        $orphanedLifeline = LifelineEquipment::whereNotIn('facility_id', $validFacilityIds)->count();
        if ($orphanedLifeline > 0) {
            $this->line("Orphaned LifelineEquipment records: {$orphanedLifeline}");
            if (!$dryRun) {
                LifelineEquipment::whereNotIn('facility_id', $validFacilityIds)->delete();
            }
        }
    }

    /**
     * Show final statistics
     */
    private function showFinalStats(): void
    {
        $this->info("\n=== Final Statistics ===");
        $this->info("FacilityInfo count: " . FacilityInfo::count());
        $this->info("FacilityBasic count: " . FacilityBasic::count());
        $this->info("FacilityService count: " . FacilityService::count());
        $this->info("LandInfo count: " . LandInfo::count());
        $this->info("BuildingInfo count: " . BuildingInfo::count());
        $this->info("LifelineEquipment count: " . LifelineEquipment::count());
    }
}