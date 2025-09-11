<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

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
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejection_reason', 'rejected_at', 'rejected_by']);
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
