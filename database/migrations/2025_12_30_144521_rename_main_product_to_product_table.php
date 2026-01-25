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
        // First rename product_item to product_variants to avoid conflict
        Schema::rename('product_item', 'product_variants');

        // Then rename main_product to product
        Schema::rename('main_product', 'product');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('product', 'main_product');
        Schema::rename('product_variants', 'product_item');
    }
};
