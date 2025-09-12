<?php

namespace App\Console\Commands;

use Database\Seeders\FacilityMasterImportSeeder;
use Illuminate\Console\Command;

class ImportFacilityMaster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'facility:import-master {--force : Force import even if facilities exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import facility data from facility_master.csv file';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting facility master import...');
        
        if (!$this->option('force')) {
            if (!$this->confirm('This will import facilities from facility_master.csv. Continue?')) {
                $this->info('Import cancelled.');
                return Command::SUCCESS;
            }
        }

        try {
            $seeder = new FacilityMasterImportSeeder();
            $seeder->setCommand($this);
            $seeder->run();
            
            $this->info('Facility master import completed successfully!');
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Import failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
