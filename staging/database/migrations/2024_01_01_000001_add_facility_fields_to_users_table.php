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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'editor', 'primary_responder', 'approver', 'viewer'])->after('email');
            $table->string('department')->nullable()->after('role');
            $table->json('access_scope')->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('access_scope');

            // Add indexes
            $table->index('role');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'department', 'access_scope', 'is_active']);
        });
    }
};
