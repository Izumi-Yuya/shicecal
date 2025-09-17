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
        Schema::table('building_infos', function (Blueprint $table) {
            $table->enum('ownership_type', ['自社', '賃借', '賃貸'])->nullable()->comment('所有任意項目')->after('facility_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('building_infos', function (Blueprint $table) {
            $table->dropColumn('ownership_type');
        });
    }
};
