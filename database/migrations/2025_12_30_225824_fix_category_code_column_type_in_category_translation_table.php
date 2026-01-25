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
        DB::statement('ALTER TABLE lkp_category_translation DROP FOREIGN KEY IF EXISTS lkp_category_translation_category_id_foreign;');
        DB::statement('ALTER TABLE lkp_category_translation DROP FOREIGN KEY IF EXISTS lkp_category_translation_category_code_foreign;');

        // Rename column from category_id to category_code if it exists
        $columns = DB::select("SHOW COLUMNS FROM lkp_category_translation LIKE 'category_id'");
        if (!empty($columns)) {
            DB::statement('ALTER TABLE lkp_category_translation CHANGE category_id category_code VARCHAR(255) NOT NULL;');
        } else {
            // Change column type from bigint to varchar if already named category_code
            DB::statement('ALTER TABLE lkp_category_translation MODIFY category_code VARCHAR(255) NOT NULL;');
        }

        // Recreate the foreign key constraint
        DB::statement('ALTER TABLE lkp_category_translation ADD CONSTRAINT lkp_category_translation_category_code_foreign FOREIGN KEY (category_code) REFERENCES lkp_category (category_code) ON DELETE CASCADE;');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lkp_category_translation', function (Blueprint $table) {
            // Drop the foreign key constraint first
            $table->dropForeign(['category_code']);

            // Change column type back from varchar to bigint
            $table->unsignedBigInteger('category_code')->change();

            // Recreate the foreign key constraint
            $table->foreign('category_code')->references('category_code')->on('lkp_category')->onDelete('cascade');
        });
    }
};
