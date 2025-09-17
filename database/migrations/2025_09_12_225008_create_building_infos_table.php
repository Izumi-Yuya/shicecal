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
        Schema::create('building_infos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');

            // 建築面積・延床面積
            $table->decimal('building_area_sqm', 10, 2)->nullable()->comment('建築面積（㎡）');
            $table->decimal('building_area_tsubo', 10, 2)->nullable()->comment('建築面積（坪数）');
            $table->decimal('total_floor_area_sqm', 10, 2)->nullable()->comment('延床面積（㎡）');
            $table->decimal('total_floor_area_tsubo', 10, 2)->nullable()->comment('延床面積（坪数）');

            // 建築費用
            $table->bigInteger('construction_cost')->nullable()->comment('本体価格（建築費用）');
            $table->decimal('cost_per_tsubo', 10, 2)->nullable()->comment('坪単価（自動計算）');
            $table->bigInteger('construction_cooperation_fee')->nullable()->comment('建設協力金');
            $table->integer('monthly_rent')->nullable()->comment('家賃（月）');

            // 契約期間
            $table->date('contract_start_date')->nullable()->comment('契約開始日');
            $table->date('contract_end_date')->nullable()->comment('契約終了日');
            $table->boolean('auto_renewal')->nullable()->comment('自動更新の有無');
            $table->integer('contract_years')->nullable()->comment('契約年数（自動計算）');

            // 管理会社情報
            $table->string('management_company_name')->nullable()->comment('管理会社（会社名）');
            $table->string('management_company_postal_code', 10)->nullable()->comment('管理会社（郵便番号）');
            $table->string('management_company_address')->nullable()->comment('管理会社（住所）');
            $table->string('management_company_building_name')->nullable()->comment('管理会社（住所建物名）');
            $table->string('management_company_phone', 20)->nullable()->comment('管理会社（電話番号）');
            $table->string('management_company_fax', 20)->nullable()->comment('管理会社（FAX番号）');
            $table->string('management_company_email')->nullable()->comment('管理会社（メールアドレス）');
            $table->string('management_company_url')->nullable()->comment('管理会社（URL）');
            $table->text('management_company_notes')->nullable()->comment('管理会社（備考欄）');

            // オーナー情報
            $table->string('owner_name')->nullable()->comment('オーナー（氏名・会社名）');
            $table->string('owner_postal_code', 10)->nullable()->comment('オーナー（郵便番号）');
            $table->string('owner_address')->nullable()->comment('オーナー（住所）');
            $table->string('owner_building_name')->nullable()->comment('オーナー（住所建物名）');
            $table->string('owner_phone', 20)->nullable()->comment('オーナー（電話番号）');
            $table->string('owner_fax', 20)->nullable()->comment('オーナー（FAX番号）');
            $table->string('owner_email')->nullable()->comment('オーナー（メールアドレス）');
            $table->string('owner_url')->nullable()->comment('オーナー（URL）');
            $table->text('owner_notes')->nullable()->comment('オーナー（備考欄）');

            // 施工会社情報
            $table->string('construction_company_name')->nullable()->comment('施工会社（会社名）');
            $table->string('construction_company_phone', 20)->nullable()->comment('施工会社（電話番号）');
            $table->text('construction_company_notes')->nullable()->comment('施工会社備考欄');

            // 建築情報
            $table->date('completion_date')->nullable()->comment('竣工日');
            $table->integer('building_age')->nullable()->comment('築年数（自動計算）');
            $table->integer('useful_life')->nullable()->comment('耐用年数');

            // 法定書類PDF
            $table->string('building_permit_pdf')->nullable()->comment('建築確認済証法定PDF');
            $table->string('building_inspection_pdf')->nullable()->comment('建築検査済証法定PDF');
            $table->string('fire_equipment_inspection_pdf')->nullable()->comment('消防用設備等検査済証法定PDF');

            // 特定建築物定期調査
            $table->enum('periodic_inspection_type', ['自社', '他社'])->nullable()->comment('特定建築物定期調査（自社or他社）');
            $table->date('periodic_inspection_date')->nullable()->comment('特定建築物定期調査（実施日）');
            $table->string('periodic_inspection_pdf')->nullable()->comment('特定建築物定期調査法定PDF');
            $table->text('periodic_inspection_notes')->nullable()->comment('特定建築物定期調査（備考）');

            // その他契約書類PDF
            $table->string('construction_contract_pdf')->nullable()->comment('工事請負契約書PDF');
            $table->string('lease_contract_pdf')->nullable()->comment('賃貸借契約書・覚書PDF');
            $table->string('registry_pdf')->nullable()->comment('謄本PDF');

            // 備考
            $table->text('notes')->nullable()->comment('備考欄');

            $table->timestamps();

            // インデックス
            $table->index('facility_id');
            $table->index('completion_date');
            $table->index('contract_start_date');
            $table->index('contract_end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('building_infos');
    }
};
