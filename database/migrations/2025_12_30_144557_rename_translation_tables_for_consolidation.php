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
        // Drop existing product_translation if it exists (from dump)
        Schema::dropIfExists('product_translation');

        // Rename main_product_translation to product_translation
        Schema::rename('main_product_translation', 'product_translation');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::rename('product_translation', 'main_product_translation');
        Schema::rename('product_variant_translation', 'product_translation');
    }
};
