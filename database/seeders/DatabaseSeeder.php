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
            UserSeeder::class,
            SystemSettingsSeeder::class,
            // FacilityDummyDataSeeder::class, // Disabled - seeder does not exist
            FacilityMasterImportSeeder::class, // Import real facility data from CSV
            FacilitySeeder::class, // Re-enabled for basic facility data
            LandInfoSeeder::class,
            FacilityServiceSeeder::class, // Re-enabled for service data
            FacilityBasicInfoSeeder::class,
            MaintenanceHistorySeeder::class,
            TestDataSeeder::class,
        ]);
    }
}
