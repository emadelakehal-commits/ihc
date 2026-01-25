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
        // Create the category hierarchy junction table
        Schema::create('category_hierarchy', function (Blueprint $table) {
            $table->string('category_code', 255);
            $table->string('parent_code', 255);
            $table->timestamps();

            $table->primary(['category_code', 'parent_code']);
            $table->foreign('category_code')->references('category_code')->on('lkp_category')->onDelete('cascade');
            $table->foreign('parent_code')->references('category_code')->on('lkp_category')->onDelete('cascade');
        });

        // Migrate existing parent relationships to the new table
        DB::statement('
            INSERT INTO category_hierarchy (category_code, parent_code, created_at, updated_at)
            SELECT category_code, parent_code, NOW(), NOW()
            FROM lkp_category
            WHERE parent_code IS NOT NULL
        ');

        // Skip removing parent_code column as it was already removed in previous migration
        // This migration is now just for creating the hierarchy table
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back the parent_code column
        Schema::table('lkp_category', function (Blueprint $table) {
            $table->string('parent_code', 255)->nullable()->after('category_code');
            $table->foreign('parent_code')->references('category_code')->on('lkp_category')->onDelete('cascade');
        });

        // Migrate data back from category_hierarchy to parent_code
        // Note: This will only work if each category has at most one parent
        DB::statement('
            UPDATE lkp_category c
            INNER JOIN category_hierarchy ch ON c.category_code = ch.category_code
            SET c.parent_code = ch.parent_code
        ');

        // Drop the category_hierarchy table
        Schema::dropIfExists('category_hierarchy');
    }
};
