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
        Schema::table('facility_drawings', function (Blueprint $table) {
            // 竣工時引き渡し図面
            $table->string('completion_drawings_filename')->nullable()->comment('竣工図面一式ファイル名');
            $table->string('completion_drawings_path')->nullable()->comment('竣工図面一式ファイルパス');
            $table->text('completion_drawings_notes')->nullable()->comment('竣工時引き渡し図面備考');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facility_drawings', function (Blueprint $table) {
            $table->dropColumn([
                'completion_drawings_filename',
                'completion_drawings_path',
                'completion_drawings_notes',
            ]);
        });
    }
};