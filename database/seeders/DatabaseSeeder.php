<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            AdminUserSeeder::class,
            SystemSettingsSeeder::class,
            FacilityDummyDataSeeder::class, // Add dummy facility data including "あおぞらの里 グループホーム小松川"
            FacilityMasterImportSeeder::class, // Import real facility data from CSV
            // FacilitySeeder::class, // Disabled - using real data from CSV instead
            LandInfoSeeder::class,
            // FacilityServiceSeeder::class, // Disabled - services are created by FacilityMasterImportSeeder
            FacilityBasicInfoSeeder::class,
            MaintenanceHistorySeeder::class,
            TestDataSeeder::class,
        ]);
    }
}
