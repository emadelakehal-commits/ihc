<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Use raw SQL to handle the foreign key constraint more reliably
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign key if it exists
        DB::statement('ALTER TABLE lkp_category DROP FOREIGN KEY IF EXISTS lkp_category_parent_code_foreign;');

        // Drop the parent_code column if it exists
        if (Schema::hasColumn('lkp_category', 'parent_code')) {
            DB::statement('ALTER TABLE lkp_category DROP COLUMN parent_code;');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lkp_category', function (Blueprint $table) {
            // Add back the parent_code column
            $table->string('parent_code', 255)->nullable()->after('category_code');

            // Recreate the foreign key constraint
            $table->foreign('parent_code')->references('category_code')->on('lkp_category')->onDelete('cascade');
        });
    }
};
