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
        Schema::table('product_attribute_value', function (Blueprint $table) {
            // Drop existing foreign keys first
            try {
                $table->dropForeign(['attribute_name']);
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
            try {
                $table->dropForeign(['product_code']);
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }

            // Drop existing primary key
            $table->dropPrimary();

            // Add language column with default 'en' for existing records
            $table->string('language', 10)->default('en')->after('attribute_name');

            // Create new composite primary key
            $table->primary(['product_code', 'attribute_name', 'language']);

            // Add foreign key constraints
            $table->foreign('attribute_name')->references('name')->on('lkp_attribute')->onDelete('cascade');
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attribute_value', function (Blueprint $table) {
            // Drop the new foreign key constraints
            $table->dropForeign(['attribute_name']);
            $table->dropForeign(['product_code']);
            $table->dropForeign(['language']);

            // Drop the new primary key
            $table->dropPrimary();

            // Drop language column
            $table->dropColumn('language');

            // Restore original primary key
            $table->primary(['product_code', 'attribute_name']);

            // Recreate original foreign key constraints
            $table->foreign('attribute_name', 'FK_product_attribute_value_attribute')->references('name')->on('lkp_attribute')->onDelete('cascade');
            $table->foreign('product_code', 'product_attribute_value_ibfk_1')->references('product_code')->on('product')->onDelete('cascade');
        });
    }
};
