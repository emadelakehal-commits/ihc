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
            // Rename the column from product_code to product_item_code
            $table->renameColumn('product_code', 'product_item_code');

            // Add the new foreign key constraint to product_item.product_item_code
            $table->foreign('product_item_code')->references('product_item_code')->on('product_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_attribute_value', function (Blueprint $table) {
            // Drop the new foreign key constraint
            $table->dropForeign(['product_item_code']);

            // Rename the column back from product_item_code to product_code
            $table->renameColumn('product_item_code', 'product_code');

            // Add back the original foreign key constraint
            $table->foreign('product_code')->references('product_item_code')->on('product_item');
        });
    }
};
