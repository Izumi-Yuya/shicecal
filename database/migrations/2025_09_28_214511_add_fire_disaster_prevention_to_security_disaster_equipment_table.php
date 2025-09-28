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
        Schema::table('security_disaster_equipment', function (Blueprint $table) {
            $table->json('fire_disaster_prevention')->nullable()->after('emergency_equipment');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('security_disaster_equipment', function (Blueprint $table) {
            $table->dropColumn('fire_disaster_prevention');
        });
    }
};
