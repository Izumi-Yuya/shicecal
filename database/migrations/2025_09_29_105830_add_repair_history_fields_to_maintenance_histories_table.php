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
            // Add category and subcategory fields for repair history classification
            $table->string('category', 50)->default('other')->after('created_by');
            $table->string('subcategory', 50)->nullable()->after('category');

            // Add contractor contact information fields for improved communication tracking
            $table->string('contact_person', 255)->nullable()->after('contractor');
            $table->string('phone_number', 20)->nullable()->after('contact_person');

            // Add classification and notes fields for comprehensive record-keeping
            $table->string('classification', 100)->nullable()->after('phone_number');
            $table->text('notes')->nullable()->after('classification');

            // Add warranty period information fields for maintenance schedule tracking
            $table->integer('warranty_period_years')->nullable()->after('notes');
            $table->date('warranty_start_date')->nullable()->after('warranty_period_years');
            $table->date('warranty_end_date')->nullable()->after('warranty_start_date');
            $table->text('warranty_notes')->nullable()->after('warranty_end_date');

            // Add indexes for performance
            $table->index(['facility_id', 'category']);
            $table->index(['category', 'subcategory']);
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
            // Drop indexes first
            $table->dropIndex(['facility_id', 'category']);
            $table->dropIndex(['category', 'subcategory']);

            // Drop added columns
            $table->dropColumn([
                'category',
                'subcategory',
                'contact_person',
                'phone_number',
                'classification',
                'notes',
                'warranty_period_years',
                'warranty_start_date',
                'warranty_end_date',
                'warranty_notes'
            ]);
        });
    }
};
