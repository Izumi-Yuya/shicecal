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
        Schema::create('security_disaster_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lifeline_equipment_id')->constrained('lifeline_equipment')->onDelete('cascade');
            $table->json('basic_info')->nullable()->comment('基本情報');
            $table->json('security_systems')->nullable()->comment('防犯システム情報');
            $table->json('disaster_prevention')->nullable()->comment('防災システム情報');
            $table->json('emergency_equipment')->nullable()->comment('緊急設備情報');
            $table->json('maintenance_records')->nullable()->comment('保守記録');
            $table->text('notes')->nullable()->comment('備考');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            $table->index(['lifeline_equipment_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('security_disaster_equipment');
    }
};
