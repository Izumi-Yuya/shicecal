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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id');
            $table->string('original_name');
            $table->string('file_path', 500);
            $table->bigInteger('file_size');
            $table->string('mime_type', 100);
            $table->enum('file_type', ['contract', 'blueprint', 'inspection', 'other'])->default('other');
            $table->unsignedBigInteger('uploaded_by');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('facility_id');
            $table->index('file_type');
            $table->index('uploaded_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('files');
    }
};
