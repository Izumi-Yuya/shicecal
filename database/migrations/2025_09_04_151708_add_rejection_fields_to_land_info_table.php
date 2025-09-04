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
        Schema::table('land_info', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable()->after('approved_by');
            $table->timestamp('rejected_at')->nullable()->after('rejection_reason');
            $table->bigInteger('rejected_by')->unsigned()->nullable()->after('rejected_at');

            $table->foreign('rejected_by')->references('id')->on('users');
        });
    }

    /**
     * Run the migrations.
     */
    public function down(): void
    {
        Schema::table('land_info', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropColumn(['rejection_reason', 'rejected_at', 'rejected_by']);
        });
    }
};
