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
            // Add content field for repair details
            $table->text('content')->nullable()->after('maintenance_date');
            
            // Add cost field for repair expenses
            $table->decimal('cost', 12, 2)->nullable()->after('content');
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
            $table->dropColumn(['content', 'cost']);
        });
    }
};