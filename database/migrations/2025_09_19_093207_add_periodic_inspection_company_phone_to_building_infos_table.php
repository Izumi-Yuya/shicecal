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
            $table->string('periodic_inspection_company_phone', 20)->nullable()->comment('定期調査会社（連絡先）')->after('periodic_inspection_type');
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
            $table->dropColumn('periodic_inspection_company_phone');
        });
    }
};
