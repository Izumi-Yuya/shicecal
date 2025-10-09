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
        Schema::table('export_favorites', function (Blueprint $table) {
            $table->enum('type', ['csv', 'pdf'])->default('csv')->after('name');
            $table->json('options')->nullable()->after('export_fields');
            
            $table->index(['user_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('export_favorites', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'type']);
            $table->dropColumn(['type', 'options']);
        });
    }
};
