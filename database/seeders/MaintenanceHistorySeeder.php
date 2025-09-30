<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\MaintenanceHistory;
use App\Models\User;
use Illuminate\Database\Seeder;

class MaintenanceHistorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Get existing facilities and users
        $facilities = Facility::where('status', 'approved')->get();
        $editors = User::whereIn('role', ['editor', 'admin'])->get();

        if ($facilities->isEmpty() || $editors->isEmpty()) {
            $this->command->info('No approved facilities or editor users found. Skipping maintenance history seeding.');

            return;
        }

        // Create repair history data for each facility
        foreach ($facilities as $facility) {
            $editor = $editors->random();

            // Create exterior maintenance histories (防水・塗装)
            $exteriorCount = rand(2, 4);
            for ($i = 0; $i < $exteriorCount; $i++) {
                MaintenanceHistory::factory()->exterior()->create([
                    'facility_id' => $facility->id,
                    'created_by' => $editor->id,
                ]);
            }

            // Create interior maintenance histories (内装リニューアル・意匠)
            $interiorCount = rand(1, 3);
            for ($i = 0; $i < $interiorCount; $i++) {
                MaintenanceHistory::factory()->interior()->create([
                    'facility_id' => $facility->id,
                    'created_by' => $editor->id,
                ]);
            }

            // Create other maintenance histories (改修工事)
            $otherCount = rand(3, 6);
            for ($i = 0; $i < $otherCount; $i++) {
                MaintenanceHistory::factory()->other()->create([
                    'facility_id' => $facility->id,
                    'created_by' => $editor->id,
                ]);
            }
        }

        $this->command->info('Repair history maintenance data seeded successfully.');
    }
}
