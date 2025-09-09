<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('land_info', function (Blueprint $table) {
            // Primary lookup index for facility relationship
            $table->index('facility_id', 'idx_land_info_facility_id');

            // Status index for approval workflow queries
            $table->index('status', 'idx_land_info_status');

            // Ownership type index for filtering and reporting
            $table->index('ownership_type', 'idx_land_info_ownership_type');

            // Composite index for status + facility queries (common in approval workflows)
            $table->index(['status', 'facility_id'], 'idx_land_info_status_facility');

            // Date indexes for contract period queries
            $table->index('contract_start_date', 'idx_land_info_contract_start');
            $table->index('contract_end_date', 'idx_land_info_contract_end');

            // Approval workflow indexes
            $table->index('approved_by', 'idx_land_info_approved_by');
            $table->index('approved_at', 'idx_land_info_approved_at');

            // User tracking indexes
            $table->index('created_by', 'idx_land_info_created_by');
            $table->index('updated_by', 'idx_land_info_updated_by');

            // Timestamp indexes for audit and reporting
            $table->index('created_at', 'idx_land_info_created_at');
            $table->index('updated_at', 'idx_land_info_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('land_info', function (Blueprint $table) {
            $table->dropIndex('idx_land_info_facility_id');
            $table->dropIndex('idx_land_info_status');
            $table->dropIndex('idx_land_info_ownership_type');
            $table->dropIndex('idx_land_info_status_facility');
            $table->dropIndex('idx_land_info_contract_start');
            $table->dropIndex('idx_land_info_contract_end');
            $table->dropIndex('idx_land_info_approved_by');
            $table->dropIndex('idx_land_info_approved_at');
            $table->dropIndex('idx_land_info_created_by');
            $table->dropIndex('idx_land_info_updated_by');
            $table->dropIndex('idx_land_info_created_at');
            $table->dropIndex('idx_land_info_updated_at');
        });
    }
};
