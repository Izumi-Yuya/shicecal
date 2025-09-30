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
            // Remove unused fields from database
            $table->dropColumn(['content', 'cost', 'classification']);
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
            // Restore fields for rollback
            $table->text('content')->nullable()->after('contractor');
            $table->decimal('cost', 10, 2)->nullable()->after('content');
            $table->string('classification', 100)->nullable()->after('phone_number');
        });
    }
};