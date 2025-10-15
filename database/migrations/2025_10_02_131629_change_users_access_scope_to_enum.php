<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Step 1: Add new enum column temporarily
        Schema::table('users', function (Blueprint $table) {
            $table->enum('access_scope_new', [
                'all_facilities',
                'assigned_facility',
                'own_facility'
            ])->nullable()->after('access_scope');
        });

        // Step 2: Migrate existing data from JSON/string to enum format
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            $newAccessScope = 'all_facilities'; // Default value
            
            if ($user->access_scope) {
                // The access_scope might be stored as JSON or as a plain string
                $accessScopeValue = $user->access_scope;
                
                // Try to decode as JSON first
                $decodedValue = json_decode($accessScopeValue, true);
                
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Successfully decoded JSON
                    if (is_string($decodedValue)) {
                        $accessScopeValue = $decodedValue;
                    } elseif (is_array($decodedValue)) {
                        // Handle complex JSON structures - for now, set to default
                        $accessScopeValue = null;
                    }
                }
                // If not JSON, treat as plain string
                
                if ($accessScopeValue) {
                    $newAccessScope = $this->mapJapaneseToEnglish($accessScopeValue);
                }
            }
            
            // Update the new column
            DB::table('users')
                ->where('id', $user->id)
                ->update(['access_scope_new' => $newAccessScope]);
        }

        // Step 3: Drop the old JSON column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_scope');
        });

        // Step 4: Rename the new column to the original name
        // Use Schema::rename for SQLite compatibility
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('access_scope_new', 'access_scope');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Step 1: Add temporary JSON column
        Schema::table('users', function (Blueprint $table) {
            $table->json('access_scope_old')->nullable()->after('access_scope');
        });

        // Step 2: Migrate data back to JSON format
        $users = DB::table('users')->get();
        
        foreach ($users as $user) {
            $jsonAccessScope = null;
            
            if ($user->access_scope) {
                // Convert enum back to Japanese string and store as JSON
                $japaneseValue = $this->mapEnglishToJapanese($user->access_scope);
                $jsonAccessScope = json_encode($japaneseValue);
            }
            
            DB::table('users')
                ->where('id', $user->id)
                ->update(['access_scope_old' => $jsonAccessScope]);
        }

        // Step 3: Drop the enum column
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('access_scope');
        });

        // Step 4: Rename the JSON column back
        // Use Schema::rename for SQLite compatibility
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('access_scope_old', 'access_scope');
        });
    }

    /**
     * Map Japanese access scope values to English constants.
     */
    private function mapJapaneseToEnglish(string $japaneseValue): string
    {
        $mapping = [
            '全事業所' => 'all_facilities',
            '担当エリアの事業所（複数）' => 'assigned_facility',
            '担当エリアのみ閲覧（複数）' => 'assigned_facility',
            '自施設のみ' => 'own_facility',
        ];

        return $mapping[$japaneseValue] ?? 'all_facilities';
    }

    /**
     * Map English constants back to Japanese values (for rollback)
     */
    private function mapEnglishToJapanese(string $englishValue): string
    {
        $mapping = [
            'all_facilities' => '全事業所',
            'assigned_facility' => '担当エリアの事業所（複数）',
            'own_facility' => '自施設のみ',
        ];

        return $mapping[$englishValue] ?? '全事業所';
    }
};
