<?php

namespace Database\Seeders;

use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Seeder;

class FacilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create some test users first if they don't exist
        if (User::count() === 0) {
            $this->call(TestUserSeeder::class);
        }

        // Get existing users to assign as creators/approvers
        $users = User::all();
        
        if ($users->isEmpty()) {
            // Create a basic user if none exist
            $user = User::factory()->create([
                'email' => 'test@example.com',
                'role' => 'editor'
            ]);
            $users = collect([$user]);
        }

        // Create approved facilities
        Facility::factory()
            ->count(15)
            ->approved()
            ->create([
                'created_by' => $users->random()->id,
                'updated_by' => $users->random()->id,
                'approved_by' => $users->random()->id,
            ]);

        // Create pending approval facilities
        Facility::factory()
            ->count(5)
            ->pendingApproval()
            ->create([
                'created_by' => $users->random()->id,
                'updated_by' => $users->random()->id,
            ]);

        // Create draft facilities
        Facility::factory()
            ->count(3)
            ->draft()
            ->create([
                'created_by' => $users->random()->id,
                'updated_by' => $users->random()->id,
            ]);

        $this->command->info('Created ' . Facility::count() . ' facilities');
    }
}