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
        Schema::table('land_info', function (Blueprint $table) {
            // PDFファイル関連フィールドを追加 - 既存チェック
            if (! Schema::hasColumn('land_info', 'lease_contract_pdf_path')) {
                $table->string('lease_contract_pdf_path')->nullable()->comment('賃貸借契約書・覚書PDF');
            }
            if (! Schema::hasColumn('land_info', 'lease_contract_pdf_name')) {
                $table->string('lease_contract_pdf_name')->nullable()->comment('賃貸借契約書・覚書PDFファイル名');
            }
            if (! Schema::hasColumn('land_info', 'registry_pdf_path')) {
                $table->string('registry_pdf_path')->nullable()->comment('謄本PDF');
            }
            if (! Schema::hasColumn('land_info', 'registry_pdf_name')) {
                $table->string('registry_pdf_name')->nullable()->comment('謄本PDFファイル名');
            }

            // 拒否関連フィールドを追加（承認ワークフロー用）- 既存チェック
            if (! Schema::hasColumn('land_info', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->comment('拒否理由');
            }
            if (! Schema::hasColumn('land_info', 'rejected_at')) {
                $table->timestamp('rejected_at')->nullable()->comment('拒否日時');
            }
            if (! Schema::hasColumn('land_info', 'rejected_by')) {
                $table->unsignedBigInteger('rejected_by')->nullable()->comment('拒否者');
                $table->foreign('rejected_by')->references('id')->on('users');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('land_info', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn([
                'lease_contract_pdf_path',
                'lease_contract_pdf_name',
                'registry_pdf_path',
                'registry_pdf_name',
                'rejection_reason',
                'rejected_at',
                'rejected_by',
            ]);
        });
    }
};
