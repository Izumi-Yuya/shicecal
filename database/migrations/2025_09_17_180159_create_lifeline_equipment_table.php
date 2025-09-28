<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Only create table if it doesn't exist
        if (! Schema::hasTable('lifeline_equipment')) {
            Schema::create('lifeline_equipment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('facility_id')->constrained()->onDelete('cascade');
                $table->string('category'); // electrical, gas, water, elevator, hvac_lighting
                $table->string('status')->default('active'); // active, inactive, decommissioned, draft, pending_approval, approved, rejected
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();

                // Indexes for performance
                $table->index(['facility_id', 'category']);
                $table->index('status');

                // Ensure unique category per facility
                $table->unique(['facility_id', 'category']);
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lifeline_equipment');
    }
};
