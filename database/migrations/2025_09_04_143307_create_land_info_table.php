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
        Schema::create('land_info', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');

            // 基本情報
            $table->enum('ownership_type', ['owned', 'leased', 'owned_rental'])->comment('所有形態: 自社/賃借/自社（賃貸）');
            $table->integer('parking_spaces')->nullable()->comment('敷地内駐車場台数');

            // 面積情報
            $table->decimal('site_area_sqm', 10, 2)->nullable()->comment('敷地面積(㎡)');
            $table->decimal('site_area_tsubo', 10, 2)->nullable()->comment('敷地面積(坪数)');

            // 金額情報（自社の場合）
            $table->decimal('purchase_price', 15, 0)->nullable()->comment('購入金額');
            $table->decimal('unit_price_per_tsubo', 15, 0)->nullable()->comment('坪単価（自動計算）');

            // 賃借情報
            $table->decimal('monthly_rent', 15, 0)->nullable()->comment('家賃');
            $table->date('contract_start_date')->nullable()->comment('契約開始日');
            $table->date('contract_end_date')->nullable()->comment('契約終了日');
            $table->enum('auto_renewal', ['yes', 'no'])->nullable()->comment('自動更新の有無');
            $table->string('contract_period_text', 50)->nullable()->comment('契約年数（自動計算）');

            // 管理会社情報
            $table->string('management_company_name', 30)->nullable()->comment('管理会社（会社名）');
            $table->string('management_company_postal_code', 8)->nullable()->comment('管理会社（郵便番号）');
            $table->string('management_company_address', 30)->nullable()->comment('管理会社（住所）');
            $table->string('management_company_building', 20)->nullable()->comment('管理会社（住所建物名）');
            $table->string('management_company_phone', 13)->nullable()->comment('管理会社（電話番号）');
            $table->string('management_company_fax', 13)->nullable()->comment('管理会社（FAX番号）');
            $table->string('management_company_email', 100)->nullable()->comment('管理会社（メールアドレス）');
            $table->string('management_company_url', 100)->nullable()->comment('管理会社（URL）');
            $table->text('management_company_notes')->nullable()->comment('管理会社（備考）');

            // オーナー情報
            $table->string('owner_name', 30)->nullable()->comment('オーナー（氏名・会社名）');
            $table->string('owner_postal_code', 8)->nullable()->comment('オーナー（郵便番号）');
            $table->string('owner_address', 30)->nullable()->comment('オーナー（住所）');
            $table->string('owner_building', 20)->nullable()->comment('オーナー（住所建物名）');
            $table->string('owner_phone', 13)->nullable()->comment('オーナー（電話番号）');
            $table->string('owner_fax', 13)->nullable()->comment('オーナー（FAX番号）');
            $table->string('owner_email', 100)->nullable()->comment('オーナー（メールアドレス）');
            $table->string('owner_url', 100)->nullable()->comment('オーナー（URL）');
            $table->text('owner_notes')->nullable()->comment('オーナー（備考欄）');

            // その他
            $table->text('notes')->nullable()->comment('備考欄');

            // システム項目
            $table->enum('status', ['draft', 'pending_approval', 'approved'])->default('approved');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Foreign keys
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('updated_by')->references('id')->on('users');

            // Unique constraint
            $table->unique('facility_id', 'unique_facility_land');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('land_info');
    }
};
