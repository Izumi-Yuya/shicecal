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
        Schema::table('facilities', function (Blueprint $table) {
            // Add special notes fields for repair history
            $table->text('exterior_special_notes')->nullable()->after('updated_by');
            $table->text('interior_special_notes')->nullable()->after('exterior_special_notes');
            $table->text('other_special_notes')->nullable()->after('interior_special_notes');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('facilities', function (Blueprint $table) {
            $table->dropColumn([
                'exterior_special_notes',
                'interior_special_notes',
                'other_special_notes',
            ]);
        });
    }
};