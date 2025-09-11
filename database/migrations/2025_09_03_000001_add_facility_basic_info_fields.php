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
        Schema::table('facilities', function (Blueprint $table) {
            // 住所関連
            $table->text('building_name')->nullable()->after('address'); // 住所（建物名）

            // 連絡先関連
            $table->string('toll_free_number', 20)->nullable()->after('fax_number'); // フリーダイヤル
            $table->string('email', 255)->nullable()->after('toll_free_number'); // メールアドレス
            $table->string('website_url', 500)->nullable()->after('email'); // URL

            // 開設関連
            $table->date('opening_date')->nullable()->after('website_url'); // 開設日
            $table->integer('years_in_operation')->nullable()->after('opening_date'); // 開設年数

            // 建物関連
            $table->string('building_structure', 100)->nullable()->after('years_in_operation'); // 建物構造
            $table->integer('building_floors')->nullable()->after('building_structure'); // 建物階数

            // 施設関連
            $table->integer('paid_rooms_count')->nullable()->after('building_floors'); // 居室数（有料）
            $table->integer('ss_rooms_count')->nullable()->after('paid_rooms_count'); // 内SS数
            $table->integer('capacity')->nullable()->after('ss_rooms_count'); // 定員数

            // サービス関連
            $table->json('service_types')->nullable()->after('capacity'); // サービスの種類
            $table->date('designation_renewal_date')->nullable()->after('service_types'); // 指定更新
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn([
                'building_name',
                'toll_free_number',
                'email',
                'website_url',
                'opening_date',
                'years_in_operation',
                'building_structure',
                'building_floors',
                'paid_rooms_count',
                'ss_rooms_count',
                'capacity',
                'service_types',
                'designation_renewal_date',
            ]);
        });
    }
};
