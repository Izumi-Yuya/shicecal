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
        Schema::table('facility_services', function (Blueprint $table) {
            $table->string('care_insurance_business_number')->nullable()->after('service_type');
            $table->string('insurer')->nullable()->after('care_insurance_business_number');
            $table->date('designation_date')->nullable()->after('insurer');
            $table->integer('remaining_months')->nullable()->after('renewal_end_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facility_services', function (Blueprint $table) {
            $table->dropColumn([
                'care_insurance_business_number',
                'insurer',
                'designation_date',
                'remaining_months'
            ]);
        });
    }
};
