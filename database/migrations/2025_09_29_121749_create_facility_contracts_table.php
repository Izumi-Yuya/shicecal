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
        Schema::create('facility_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            
            // その他契約書
            $table->string('others_company_name')->nullable();
            $table->string('others_contract_type')->nullable();
            $table->text('others_contract_content')->nullable();
            $table->enum('others_auto_renewal', ['あり', 'なし', '条件付き'])->nullable();
            $table->text('others_auto_renewal_details')->nullable();
            $table->date('others_contract_start_date')->nullable();
            $table->text('others_cancellation_conditions')->nullable();
            $table->string('others_renewal_notice_period')->nullable();
            $table->date('others_contract_end_date')->nullable();
            $table->text('others_other_matters')->nullable();
            $table->bigInteger('others_amount')->nullable();
            $table->text('others_contact_info')->nullable();
            
            // 給食契約書（今後拡張予定）
            $table->json('meal_service_data')->nullable();
            
            // 駐車場契約書（今後拡張予定）
            $table->json('parking_data')->nullable();
            
            $table->timestamps();
            
            // 1つの施設につき1つの契約書レコード
            $table->unique('facility_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_contracts');
    }
};