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
        Schema::table('facility_drawings', function (Blueprint $table) {
            // 引き渡し図面の施工図面一式（1行目固定）
            $table->string('construction_drawings_filename')->nullable()->after('kitchen_equipment_path');
            $table->string('construction_drawings_path')->nullable()->after('construction_drawings_filename');
            
            // 引き渡し図面の追加図面と備考（JSON形式）
            $table->json('handover_drawings')->nullable()->after('construction_drawings_path');
            
            // 既存の竣工図面関連フィールドを削除（新しい構造に統合）
            $table->dropColumn(['completion_drawings_filename', 'completion_drawings_path', 'completion_drawings_notes']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facility_drawings', function (Blueprint $table) {
            // 引き渡し図面フィールドを削除
            $table->dropColumn(['construction_drawings_filename', 'construction_drawings_path', 'handover_drawings']);
            
            // 既存の竣工図面関連フィールドを復元
            $table->string('completion_drawings_filename')->nullable();
            $table->string('completion_drawings_path')->nullable();
            $table->text('completion_drawings_notes')->nullable();
        });
    }
};
