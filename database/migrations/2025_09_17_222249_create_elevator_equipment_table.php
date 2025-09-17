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
        Schema::create('elevator_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lifeline_equipment_id')->constrained('lifeline_equipment')->onDelete('cascade');
            $table->json('basic_info')->nullable()->comment('Basic elevator information (manufacturer, model, capacity, etc.)');
            $table->json('maintenance_info')->nullable()->comment('Maintenance schedule and history information');
            $table->json('safety_info')->nullable()->comment('Safety inspection and certification information');
            $table->text('notes')->nullable()->comment('Additional notes and remarks');
            $table->timestamps();

            // Add index for better query performance
            $table->index('lifeline_equipment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('elevator_equipment');
    }
};
