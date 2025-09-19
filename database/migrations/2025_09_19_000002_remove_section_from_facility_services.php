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
        Schema::table('facility_services', function (Blueprint $table) {
            $table->dropIndex(['section']); // インデックスを削除
            $table->dropColumn('section'); // カラムを削除
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facility_services', function (Blueprint $table) {
            $table->string('section')->nullable();
            $table->index('section');
        });
    }
};