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
        Schema::create('gas_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lifeline_equipment_id')->constrained('lifeline_equipment')->onDelete('cascade');
            $table->json('basic_info')->nullable();
            $table->text('notes')->nullable();
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
        Schema::dropIfExists('gas_equipment');
    }
};
