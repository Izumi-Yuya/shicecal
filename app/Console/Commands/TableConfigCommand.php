<?php

namespace App\Console\Commands;

use App\Services\TableConfigService;
use Illuminate\Console\Command;

class TableConfigCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'table:config 
                            {action : The action to perform (show, validate, clear-cache)}
                            {--type= : The table type (basic_info, service_info, land_info)}
                            {--json : Output as JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage table configurations';

    /**
     * Execute the console command.
     *
     * @param TableConfigService $configService
     * @return int
     */
    public function handle(TableConfigService $configService): int
    {
        $action = $this->argument('action');
        $type = $this->option('type');
        $json = $this->option('json');

        switch ($action) {
            case 'show':
                return $this->showConfig($configService, $type, $json);
            
            case 'validate':
                return $this->validateConfig($configService, $type);
            
            case 'clear-cache':
                return $this->clearCache($configService, $type);
            
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    /**
     * Show table configuration
     */
    private function showConfig(TableConfigService $configService, ?string $type, bool $json): int
    {
        try {
            if ($type) {
                $config = $configService->getTableConfig($type);
                $this->displayConfig($type, $config, $json);
            } else {
                // Show all configurations
                foreach ($configService->getAvailableTableTypes() as $tableType) {
                    $config = $configService->getTableConfig($tableType);
                    $this->displayConfig($tableType, $config, $json);
                    
                    if (!$json && $tableType !== end($configService->getAvailableTableTypes())) {
                        $this->line('');
                        $this->line(str_repeat('-', 50));
                        $this->line('');
                    }
                }
            }
            
            return 0;
        } catch (\Exception $e) {
            $this->error("Error showing config: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Validate table configuration
     */
    private function validateConfig(TableConfigService $configService, ?string $type): int
    {
        try {
            $types = $type ? [$type] : $configService->getAvailableTableTypes();
            $allValid = true;

            foreach ($types as $tableType) {
                $config = $configService->getTableConfig($tableType);
                $isValid = $configService->validateConfig($config);
                
                if ($isValid) {
                    $this->info("✓ {$tableType}: Valid");
                } else {
                    $this->error("✗ {$tableType}: Invalid");
                    $allValid = false;
                }
            }

            if ($allValid) {
                $this->info('All configurations are valid!');
                return 0;
            } else {
                $this->error('Some configurations are invalid.');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error validating config: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Clear configuration cache
     */
    private function clearCache(TableConfigService $configService, ?string $type): int
    {
        try {
            $result = $configService->clearCache($type);
            
            if ($result) {
                $message = $type ? "Cache cleared for {$type}" : "All table configuration caches cleared";
                $this->info($message);
                return 0;
            } else {
                $this->error("Failed to clear cache");
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("Error clearing cache: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Display configuration
     */
    private function displayConfig(string $type, array $config, bool $json): void
    {
        if ($json) {
            $this->line(json_encode([$type => $config], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info("Table Type: {$type}");
            $this->line("Layout Type: {$config['layout']['type']}");
            $this->line("Columns: " . count($config['columns']));
            $this->line("Comments Enabled: " . ($config['features']['comments'] ? 'Yes' : 'No'));
            
            $this->line("\nColumns:");
            foreach ($config['columns'] as $column) {
                $label = $column['label'] ?? 'N/A';
                $type = $column['type'] ?? 'N/A';
                $width = $column['width'] ?? 'auto';
                $this->line("  - {$column['key']}: {$label} ({$type}, {$width})");
            }
        }
    }
}