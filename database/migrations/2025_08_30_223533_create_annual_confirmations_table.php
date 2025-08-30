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
        Schema::create('annual_confirmations', function (Blueprint $table) {
            $table->id();
            $table->year('confirmation_year');
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('facility_manager_id')->nullable()->constrained('users');
            $table->enum('status', ['pending', 'confirmed', 'discrepancy_reported', 'resolved'])->default('pending');
            $table->text('discrepancy_details')->nullable();
            $table->timestamp('requested_at');
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            
            $table->unique(['confirmation_year', 'facility_id']);
            $table->index(['status', 'requested_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('annual_confirmations');
    }
};