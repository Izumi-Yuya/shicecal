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
        Schema::create('document_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('facility_id')->comment('施設ID');
            $table->unsignedBigInteger('folder_id')->nullable()->comment('フォルダID（nullの場合はルートフォルダ）');
            $table->string('original_name', 255)->comment('元のファイル名');
            $table->string('stored_name', 255)->comment('保存時のファイル名');
            $table->text('file_path')->comment('ファイルパス');
            $table->unsignedBigInteger('file_size')->comment('ファイルサイズ（バイト）');
            $table->string('mime_type', 100)->comment('MIMEタイプ');
            $table->string('file_extension', 10)->comment('ファイル拡張子');
            $table->unsignedBigInteger('uploaded_by')->comment('アップロード者ID');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('facility_id')->references('id')->on('facilities')->onDelete('cascade');
            $table->foreign('folder_id')->references('id')->on('document_folders')->onDelete('cascade');
            $table->foreign('uploaded_by')->references('id')->on('users')->onDelete('restrict');

            // Indexes for performance optimization
            $table->index('facility_id', 'idx_document_files_facility_id');
            $table->index('folder_id', 'idx_document_files_folder_id');
            $table->index(['facility_id', 'folder_id'], 'idx_document_files_facility_folder');
            $table->index('file_extension', 'idx_document_files_extension');
            $table->index('created_at', 'idx_document_files_created_at');
            $table->index('uploaded_by', 'idx_document_files_uploaded_by');
            $table->index('file_size', 'idx_document_files_size');
            
            // Index for sorting by name
            $table->index('original_name', 'idx_document_files_original_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('document_files');
    }
};
