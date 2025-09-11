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

        // Create maintenance histories for each facility
        foreach ($facilities as $facility) {
            // Create 2-5 maintenance histories per facility
            $historyCount = rand(2, 5);

            for ($i = 0; $i < $historyCount; $i++) {
                MaintenanceHistory::factory()->create([
                    'facility_id' => $facility->id,
                    'created_by' => $editors->random()->id,
                ]);
            }
        }

        $this->command->info('Maintenance histories seeded successfully.');
    }
}
