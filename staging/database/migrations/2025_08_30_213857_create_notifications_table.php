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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // 通知を受け取るユーザー
            $table->string('type'); // 通知タイプ (comment_posted, comment_status_changed, etc.)
            $table->string('title'); // 通知タイトル
            $table->text('message'); // 通知メッセージ
            $table->json('data')->nullable(); // 追加データ (comment_id, facility_id, etc.)
            $table->boolean('is_read')->default(false); // 既読フラグ
            $table->timestamp('read_at')->nullable(); // 既読日時
            $table->boolean('email_sent')->default(false); // メール送信フラグ
            $table->timestamp('email_sent_at')->nullable(); // メール送信日時
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
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
        Schema::dropIfExists('notifications');
    }
};
