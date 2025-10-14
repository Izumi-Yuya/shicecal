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
        Schema::table('maintenance_histories', function (Blueprint $table) {
            // 保証期間の年数フィールド（既存のwarranty_period_yearsを保持）
            // 保証期間の月数フィールドを追加
            $table->integer('warranty_period_months')->nullable()->after('warranty_period_years')->comment('Warranty period in months');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maintenance_histories', function (Blueprint $table) {
            $table->dropColumn('warranty_period_months');
        });
    }
};
