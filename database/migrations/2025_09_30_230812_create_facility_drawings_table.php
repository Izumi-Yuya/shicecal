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
        Schema::create('facility_drawings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            
            // 建物図面
            $table->string('floor_plan_filename')->nullable()->comment('平面図ファイル名');
            $table->string('floor_plan_path')->nullable()->comment('平面図ファイルパス');
            $table->string('site_plan_filename')->nullable()->comment('配置図ファイル名');
            $table->string('site_plan_path')->nullable()->comment('配置図ファイルパス');
            $table->string('elevation_filename')->nullable()->comment('立面図ファイル名');
            $table->string('elevation_path')->nullable()->comment('立面図ファイルパス');
            $table->string('development_filename')->nullable()->comment('展開図ファイル名');
            $table->string('development_path')->nullable()->comment('展開図ファイルパス');
            $table->string('area_calculation_filename')->nullable()->comment('求積図ファイル名');
            $table->string('area_calculation_path')->nullable()->comment('求積図ファイルパス');
            
            // 設備図面
            $table->string('electrical_equipment_filename')->nullable()->comment('電気設備図面ファイル名');
            $table->string('electrical_equipment_path')->nullable()->comment('電気設備図面ファイルパス');
            $table->string('lighting_equipment_filename')->nullable()->comment('電灯設備図面ファイル名');
            $table->string('lighting_equipment_path')->nullable()->comment('電灯設備図面ファイルパス');
            $table->string('hvac_equipment_filename')->nullable()->comment('空調設備図面ファイル名');
            $table->string('hvac_equipment_path')->nullable()->comment('空調設備図面ファイルパス');
            $table->string('plumbing_equipment_filename')->nullable()->comment('給排水衛生設備図面ファイル名');
            $table->string('plumbing_equipment_path')->nullable()->comment('給排水衛生設備図面ファイルパス');
            $table->string('kitchen_equipment_filename')->nullable()->comment('厨房設備図面ファイル名');
            $table->string('kitchen_equipment_path')->nullable()->comment('厨房設備図面ファイルパス');
            
            // 追加図面（JSON形式で柔軟に管理）
            $table->json('additional_building_drawings')->nullable()->comment('追加建物図面');
            $table->json('additional_equipment_drawings')->nullable()->comment('追加設備図面');
            
            // 備考
            $table->text('notes')->nullable()->comment('備考');
            
            $table->timestamps();
            
            $table->index(['facility_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_drawings');
    }
};