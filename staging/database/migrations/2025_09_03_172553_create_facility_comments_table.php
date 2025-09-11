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
        Schema::create('facility_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('facility_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('section'); // 'basic_info', 'contact_info', 'building_info', 'facility_info', 'services'
            $table->text('comment');
            $table->timestamps();

            $table->index(['facility_id', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facility_comments');
    }
};
