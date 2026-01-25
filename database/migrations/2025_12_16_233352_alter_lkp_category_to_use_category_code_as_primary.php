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
        // Drop all foreign keys that reference the id column in lkp_category
        Schema::table('lkp_category_translation', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('product_category', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });

        Schema::table('lkp_category', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('id');
            $table->string('category_code', 255)->primary()->first();
            $table->renameColumn('parent_id', 'parent_code');
            $table->dropColumn('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lkp_category', function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('category_code');
            $table->id()->first();
            $table->renameColumn('parent_code', 'parent_id');
            $table->string('slug');
        });
    }
};
