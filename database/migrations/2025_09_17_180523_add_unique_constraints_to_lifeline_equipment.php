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
        Schema::table('lifeline_equipment', function (Blueprint $table) {
            // Add unique constraint to ensure one equipment per category per facility
            $table->unique(['facility_id', 'category'], 'lifeline_equipment_facility_category_unique');
        });

        Schema::table('electrical_equipment', function (Blueprint $table) {
            // Add unique constraint to ensure one electrical equipment per lifeline equipment
            $table->unique('lifeline_equipment_id', 'electrical_equipment_lifeline_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lifeline_equipment', function (Blueprint $table) {
            $table->dropUnique('lifeline_equipment_facility_category_unique');
        });

        Schema::table('electrical_equipment', function (Blueprint $table) {
            $table->dropUnique('electrical_equipment_lifeline_unique');
        });
    }
};
