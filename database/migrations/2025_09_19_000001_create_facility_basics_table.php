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
        Schema::create('facility_basics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id')->unique();
            
            // 運営情報
            $table->date('opening_date')->nullable();
            $table->integer('years_in_operation')->nullable();
            $table->date('designation_renewal_date')->nullable();
            
            // 建物情報
            $table->string('building_structure')->nullable();
            $table->integer('building_floors')->nullable();
            
            // 定員・部屋数
            $table->integer('paid_rooms_count')->nullable();
            $table->integer('ss_rooms_count')->nullable();
            $table->integer('capacity')->nullable();
            
            // サービス情報（JSONで複数保存）
            $table->json('service_types')->nullable();
            
            // 部門（施設レベルの属性として追加）
            $table->string('section')->nullable();
            
            // システム管理
            $table->enum('status', ['draft', 'pending_approval', 'approved'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('facility_id');
            $table->index('section');
            $table->index('status');
            $table->index(['opening_date', 'designation_renewal_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('facility_basics');
    }
};