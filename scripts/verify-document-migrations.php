<?php

/**
 * Document Management Migration Verification Script
 * 
 * This script verifies that all document management migrations
 * have been applied correctly and the database schema is ready.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

class DocumentMigrationVerifier
{
    private $capsule;
    private $errors = [];
    private $warnings = [];
    
    public function __construct()
    {
        $this->setupDatabase();
    }
    
    private function setupDatabase()
    {
        $this->capsule = new Capsule;
        
        // Load environment variables
        if (file_exists(__DIR__ . '/../.env')) {
            $env = parse_ini_file(__DIR__ . '/../.env');
            foreach ($env as $key => $value) {
                $_ENV[$key] = $value;
            }
        }
        
        $this->capsule->addConnection([
            'driver' => $_ENV['DB_CONNECTION'] ?? 'mysql',
            'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_DATABASE'] ?? 'forge',
            'username' => $_ENV['DB_USERNAME'] ?? 'forge',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ]);
        
        $this->capsule->setAsGlobal();
        $this->capsule->bootEloquent();
    }
    
    public function verify()
    {
        echo "Document Management Migration Verification\n";
        echo "=========================================\n\n";
        
        $this->checkMigrationsTable();
        $this->checkDocumentFoldersTable();
        $this->checkDocumentFilesTable();
        $this->checkIndexes();
        $this->checkForeignKeys();
        $this->checkPermissions();
        
        $this->printResults();
        
        return empty($this->errors);
    }
    
    private function checkMigrationsTable()
    {
        echo "Checking migrations table...\n";
        
        try {
            $migrations = Capsule::table('migrations')
                ->where('migration', 'like', '%document%')
                ->get();
            
            $expectedMigrations = [
                '2025_09_29_172019_create_document_folders_table',
                '2025_09_29_172032_create_document_files_table',
            ];
            
            $foundMigrations = $migrations->pluck('migration')->toArray();
            
            foreach ($expectedMigrations as $expected) {
                if (in_array($expected, $foundMigrations)) {
                    echo "  ✓ $expected\n";
                } else {
                    $this->errors[] = "Missing migration: $expected";
                    echo "  ✗ $expected (MISSING)\n";
                }
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Cannot access migrations table: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function checkDocumentFoldersTable()
    {
        echo "Checking document_folders table...\n";
        
        try {
            $schema = Capsule::schema();
            
            if (!$schema->hasTable('document_folders')) {
                $this->errors[] = "Table 'document_folders' does not exist";
                echo "  ✗ Table does not exist\n\n";
                return;
            }
            
            echo "  ✓ Table exists\n";
            
            // Check required columns
            $requiredColumns = [
                'id' => 'bigint',
                'facility_id' => 'bigint',
                'parent_id' => 'bigint',
                'name' => 'varchar',
                'path' => 'text',
                'created_by' => 'bigint',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ];
            
            foreach ($requiredColumns as $column => $type) {
                if ($schema->hasColumn('document_folders', $column)) {
                    echo "  ✓ Column '$column' exists\n";
                } else {
                    $this->errors[] = "Missing column '$column' in document_folders table";
                    echo "  ✗ Column '$column' missing\n";
                }
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Error checking document_folders table: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function checkDocumentFilesTable()
    {
        echo "Checking document_files table...\n";
        
        try {
            $schema = Capsule::schema();
            
            if (!$schema->hasTable('document_files')) {
                $this->errors[] = "Table 'document_files' does not exist";
                echo "  ✗ Table does not exist\n\n";
                return;
            }
            
            echo "  ✓ Table exists\n";
            
            // Check required columns
            $requiredColumns = [
                'id' => 'bigint',
                'facility_id' => 'bigint',
                'folder_id' => 'bigint',
                'original_name' => 'varchar',
                'stored_name' => 'varchar',
                'file_path' => 'text',
                'file_size' => 'bigint',
                'mime_type' => 'varchar',
                'file_extension' => 'varchar',
                'uploaded_by' => 'bigint',
                'created_at' => 'timestamp',
                'updated_at' => 'timestamp',
            ];
            
            foreach ($requiredColumns as $column => $type) {
                if ($schema->hasColumn('document_files', $column)) {
                    echo "  ✓ Column '$column' exists\n";
                } else {
                    $this->errors[] = "Missing column '$column' in document_files table";
                    echo "  ✗ Column '$column' missing\n";
                }
            }
            
        } catch (Exception $e) {
            $this->errors[] = "Error checking document_files table: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function checkIndexes()
    {
        echo "Checking database indexes...\n";
        
        try {
            // Check document_folders indexes
            $this->checkTableIndexes('document_folders', [
                'idx_facility_id' => ['facility_id'],
                'idx_parent_id' => ['parent_id'],
            ]);
            
            // Check document_files indexes
            $this->checkTableIndexes('document_files', [
                'idx_facility_id' => ['facility_id'],
                'idx_folder_id' => ['folder_id'],
                'idx_file_extension' => ['file_extension'],
                'idx_created_at' => ['created_at'],
            ]);
            
        } catch (Exception $e) {
            $this->warnings[] = "Could not verify indexes: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function checkTableIndexes($table, $expectedIndexes)
    {
        $connection = Capsule::connection();
        $driver = $connection->getDriverName();
        
        if ($driver === 'mysql') {
            $indexes = $connection->select("SHOW INDEX FROM $table");
            $indexNames = array_unique(array_column($indexes, 'Key_name'));
            
            foreach ($expectedIndexes as $indexName => $columns) {
                if (in_array($indexName, $indexNames)) {
                    echo "  ✓ Index '$indexName' exists on $table\n";
                } else {
                    $this->warnings[] = "Index '$indexName' missing on $table";
                    echo "  ⚠ Index '$indexName' missing on $table\n";
                }
            }
        } else {
            echo "  ⚠ Index checking not supported for $driver driver\n";
        }
    }
    
    private function checkForeignKeys()
    {
        echo "Checking foreign key constraints...\n";
        
        try {
            $connection = Capsule::connection();
            $driver = $connection->getDriverName();
            
            if ($driver === 'mysql') {
                // Check document_folders foreign keys
                $this->checkTableForeignKeys('document_folders', [
                    'facility_id' => 'facilities',
                    'parent_id' => 'document_folders',
                    'created_by' => 'users',
                ]);
                
                // Check document_files foreign keys
                $this->checkTableForeignKeys('document_files', [
                    'facility_id' => 'facilities',
                    'folder_id' => 'document_folders',
                    'uploaded_by' => 'users',
                ]);
            } else {
                echo "  ⚠ Foreign key checking not supported for $driver driver\n";
            }
            
        } catch (Exception $e) {
            $this->warnings[] = "Could not verify foreign keys: " . $e->getMessage();
        }
        
        echo "\n";
    }
    
    private function checkTableForeignKeys($table, $expectedForeignKeys)
    {
        $connection = Capsule::connection();
        $database = $connection->getDatabaseName();
        
        $foreignKeys = $connection->select("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME
            FROM 
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE 
                TABLE_SCHEMA = ? 
                AND TABLE_NAME = ? 
                AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
        
        $existingForeignKeys = [];
        foreach ($foreignKeys as $fk) {
            $existingForeignKeys[$fk->COLUMN_NAME] = $fk->REFERENCED_TABLE_NAME;
        }
        
        foreach ($expectedForeignKeys as $column => $referencedTable) {
            if (isset($existingForeignKeys[$column]) && $existingForeignKeys[$column] === $referencedTable) {
                echo "  ✓ Foreign key '$column' -> '$referencedTable' exists on $table\n";
            } else {
                $this->warnings[] = "Foreign key '$column' -> '$referencedTable' missing on $table";
                echo "  ⚠ Foreign key '$column' -> '$referencedTable' missing on $table\n";
            }
        }
    }
    
    private function checkPermissions()
    {
        echo "Checking file system permissions...\n";
        
        $directories = [
            'storage/app/public/documents',
            'storage/logs',
        ];
        
        foreach ($directories as $dir) {
            $fullPath = __DIR__ . '/../' . $dir;
            
            if (!is_dir($fullPath)) {
                mkdir($fullPath, 0755, true);
                echo "  ✓ Created directory: $dir\n";
            } else {
                echo "  ✓ Directory exists: $dir\n";
            }
            
            if (!is_writable($fullPath)) {
                $this->warnings[] = "Directory not writable: $dir";
                echo "  ⚠ Directory not writable: $dir\n";
            } else {
                echo "  ✓ Directory writable: $dir\n";
            }
        }
        
        echo "\n";
    }
    
    private function printResults()
    {
        echo "Verification Results\n";
        echo "===================\n\n";
        
        if (empty($this->errors) && empty($this->warnings)) {
            echo "✅ All checks passed! Document management system is ready.\n";
        } else {
            if (!empty($this->errors)) {
                echo "❌ Errors found:\n";
                foreach ($this->errors as $error) {
                    echo "  • $error\n";
                }
                echo "\n";
            }
            
            if (!empty($this->warnings)) {
                echo "⚠️  Warnings:\n";
                foreach ($this->warnings as $warning) {
                    echo "  • $warning\n";
                }
                echo "\n";
            }
        }
        
        echo "Summary:\n";
        echo "  Errors: " . count($this->errors) . "\n";
        echo "  Warnings: " . count($this->warnings) . "\n";
    }
}

// Run verification
$verifier = new DocumentMigrationVerifier();
$success = $verifier->verify();

exit($success ? 0 : 1);