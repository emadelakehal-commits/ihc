<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // The foreign key already references category_code, but column is named category_id
        // Just rename the column to match
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop existing primary key
        DB::statement('ALTER TABLE product_category DROP PRIMARY KEY');

        // Rename category_id to category_code
        DB::statement('ALTER TABLE product_category CHANGE category_id category_code VARCHAR(255) NOT NULL');

        // Add primary key on product_code and category_code
        DB::statement('ALTER TABLE product_category ADD PRIMARY KEY (product_code, category_code)');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Change back to category_id
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop foreign key
        DB::statement('ALTER TABLE product_category DROP FOREIGN KEY product_category_category_code_foreign');

        // Drop primary key
        DB::statement('ALTER TABLE product_category DROP PRIMARY KEY');

        // Add category_id column
        DB::statement('ALTER TABLE product_category ADD COLUMN category_id BIGINT UNSIGNED NOT NULL AFTER product_code');

        // Populate category_id from lkp_category table
        DB::statement('UPDATE product_category pc
                      JOIN lkp_category lc ON pc.category_code = lc.category_code
                      SET pc.category_id = lc.id');

        // Drop category_code column
        DB::statement('ALTER TABLE product_category DROP COLUMN category_code');

        // Add primary key on product_code and category_id
        DB::statement('ALTER TABLE product_category ADD PRIMARY KEY (product_code, category_id)');

        // Add foreign key to lkp_category.id
        DB::statement('ALTER TABLE product_category ADD CONSTRAINT product_category_category_id_foreign
                      FOREIGN KEY (category_id) REFERENCES lkp_category(id) ON DELETE CASCADE');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }
};
