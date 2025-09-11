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
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('action', 100);
            $table->string('target_type', 100);
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description');
            $table->string('ip_address', 45);
            $table->text('user_agent');
            $table->timestamp('created_at');

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('action');
            $table->index('target_type');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('activity_logs');
    }
};
