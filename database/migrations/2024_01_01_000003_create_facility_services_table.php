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
        Schema::create('facility_services', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->string('service_type');
            $table->string('section')->nullable(); // Added missing section column
            $table->date('renewal_start_date')->nullable();
            $table->date('renewal_end_date')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');

            // Indexes
            $table->index('facility_id');
            $table->index('service_type');
            $table->index('section'); // Added index for section column
            $table->index(['renewal_start_date', 'renewal_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facility_services');
    }
};
