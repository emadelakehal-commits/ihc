<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Rename translation tables from plural to singular (only if they exist)
        $tables = [
            'lkp_category_translations' => 'lkp_category_translation',
            'product_translations' => 'product_translation',
            'main_product_translations' => 'main_product_translation',
            'lkp_tag_translations' => 'lkp_tag_translation',
            'lkp_item_tag_translations' => 'lkp_item_tag_translation',
            'product_item_translations' => 'product_item_translation',
        ];

        foreach ($tables as $oldName => $newName) {
            try {
                DB::statement("RENAME TABLE `{$oldName}` TO `{$newName}`");
            } catch (\Exception $e) {
                // Table doesn't exist or already renamed, skip
                continue;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename translation tables back from singular to plural
        DB::statement('RENAME TABLE lkp_category_translation TO lkp_category_translations');
        DB::statement('RENAME TABLE product_translation TO product_translations');
        DB::statement('RENAME TABLE main_product_translation TO main_product_translations');
        DB::statement('RENAME TABLE lkp_tag_translation TO lkp_tag_translations');
        DB::statement('RENAME TABLE lkp_item_tag_translation TO lkp_item_tag_translations');
        DB::statement('RENAME TABLE product_item_translation TO product_item_translations');
    }
};
