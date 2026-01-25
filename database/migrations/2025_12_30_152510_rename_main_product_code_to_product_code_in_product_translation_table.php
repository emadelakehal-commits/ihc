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
        Schema::table('product_translation', function (Blueprint $table) {
            // Drop the existing foreign key constraint by name
            $table->dropForeign('main_product_translation_main_product_code_foreign');
            // Rename the column
            $table->renameColumn('main_product_code', 'product_code');
            // Add the new foreign key constraint
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_translation', function (Blueprint $table) {
            // Drop the existing foreign key constraint
            $table->dropForeign(['product_code']);
            // Rename the column back
            $table->renameColumn('product_code', 'main_product_code');
            // Add the old foreign key constraint
            $table->foreign('main_product_code')->references('product_code')->on('product')->onDelete('cascade');
        });
    }
};
