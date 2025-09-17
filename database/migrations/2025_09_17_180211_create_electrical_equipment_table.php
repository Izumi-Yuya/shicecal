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
        if (!Schema::hasTable('electrical_equipment')) {
            Schema::create('electrical_equipment', function (Blueprint $table) {
                $table->id();
                $table->foreignId('lifeline_equipment_id')->constrained('lifeline_equipment')->onDelete('cascade');
                
                // JSON fields for flexible data storage based on design document
                $table->json('basic_info')->nullable(); // electrical_contractor, safety_management_company, maintenance_inspection_date, inspection_report_pdf
                $table->json('pas_info')->nullable(); // availability, update_date
                $table->json('cubicle_info')->nullable(); // availability, equipment_list with equipment_number, manufacturer, model_year, update_date
                $table->json('generator_info')->nullable(); // availability, availability_details, equipment_list with equipment_number, manufacturer, model_year, update_date
                $table->text('notes')->nullable(); // 備考
                
                $table->timestamps();
                
                // Index for performance
                $table->index('lifeline_equipment_id');
                
                // Ensure one electrical equipment per lifeline equipment
                $table->unique('lifeline_equipment_id');
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
        Schema::dropIfExists('electrical_equipment');
    }
};
