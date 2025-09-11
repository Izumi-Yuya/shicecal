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
        Schema::create('facilities', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('office_code', 20);
            $table->string('designation_number', 50)->nullable();
            $table->string('facility_name');
            $table->string('postal_code', 10)->nullable();
            $table->text('address')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->string('fax_number', 20)->nullable();
            $table->enum('status', ['draft', 'pending_approval', 'approved'])->default('draft');
            $table->timestamp('approved_at')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by');
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');

            // Indexes
            $table->index('office_code');
            $table->index('facility_name');
            $table->index('status');
            $table->index('created_by');
            $table->index('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('facilities');
    }
};
