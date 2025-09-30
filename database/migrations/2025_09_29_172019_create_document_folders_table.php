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
        Schema::create('document_folders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id')->comment('施設ID');
            $table->unsignedBigInteger('parent_id')->nullable()->comment('親フォルダID');
            $table->string('name', 255)->comment('フォルダ名');
            $table->text('path')->comment('フォルダパス');
            $table->unsignedBigInteger('created_by')->comment('作成者ID');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('document_folders')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes for performance optimization
            $table->index('facility_id', 'idx_document_folders_facility_id');
            $table->index('parent_id', 'idx_document_folders_parent_id');
            $table->index(['facility_id', 'parent_id'], 'idx_document_folders_facility_parent');
            $table->index('path', 'idx_document_folders_path');
            $table->index('created_by', 'idx_document_folders_created_by');
            
            // Unique constraint to prevent duplicate folder names in the same parent
            $table->unique(['facility_id', 'parent_id', 'name'], 'unique_folder_name_per_parent');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_folders');
    }
};
