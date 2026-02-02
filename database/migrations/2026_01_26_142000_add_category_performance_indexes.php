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
        // Add index for parent lookups (tree traversal)
        DB::statement('CREATE INDEX idx_category_hierarchy_parent_code ON category_hierarchy(parent_code)');

        // Add composite index for language filtering
        DB::statement('CREATE INDEX idx_category_translation_lang_code ON lkp_category_translation(language, category_code)');

        // Ensure we have an index on category_code in the main table (should already exist as primary key)
        // But let's make sure it's optimized for lookups
        DB::statement('ALTER TABLE lkp_category ADD INDEX idx_category_code (category_code)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the indexes
        DB::statement('DROP INDEX IF EXISTS idx_category_hierarchy_parent_code ON category_hierarchy');
        DB::statement('DROP INDEX IF EXISTS idx_category_translation_lang_code ON lkp_category_translation');
        DB::statement('DROP INDEX IF EXISTS idx_category_code ON lkp_category');
    }
};