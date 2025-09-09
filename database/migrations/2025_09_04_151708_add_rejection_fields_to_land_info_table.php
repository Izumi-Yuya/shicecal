<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('land_info', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->bigInteger('rejected_by')->unsigned()->nullable()->after('rejected_at');

            $table->foreign('rejected_by')->references('id')->on('users');
        });

        // Update the enum to include 'rejected' - handle SQLite vs MySQL differently
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN, so we'll handle this in the model validation
            // The enum constraint will be enforced at the application level
        } else {
            // MySQL supports MODIFY COLUMN
            DB::statement("ALTER TABLE land_info MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved', 'rejected') DEFAULT 'approved'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('land_info', function (Blueprint $table) {
            // SQLite doesn't support dropping foreign keys, so we'll skip it for SQLite
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                if (Schema::hasColumn('land_info', 'rejected_by')) {
                    $table->dropForeign(['rejected_by']);
                }
            }
            
            // Only drop columns that exist
            $columnsToCheck = ['rejection_reason', 'rejected_at', 'rejected_by'];
            $columnsToDrop = [];
            
            foreach ($columnsToCheck as $column) {
                if (Schema::hasColumn('land_info', $column)) {
                    $columnsToDrop[] = $column;
                }
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });

        // Revert enum back to original values - handle SQLite vs MySQL differently
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support MODIFY COLUMN, so we'll handle this in the model validation
            // The enum constraint will be enforced at the application level
        } else {
            // MySQL supports MODIFY COLUMN
            DB::statement("ALTER TABLE land_info MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved') DEFAULT 'approved'");
        }
    }
};
