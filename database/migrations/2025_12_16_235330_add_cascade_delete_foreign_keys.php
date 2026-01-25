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
        // Update existing foreign keys to add CASCADE
        Schema::table('lkp_category_translation', function (Blueprint $table) {
            $table->dropForeign('lkp_category_translation_language_foreign');
            $table->foreign('language', 'lkp_category_translation_language_foreign')->references('code')->on('lkp_language')->onDelete('cascade');
        });

        Schema::table('main_product_translation', function (Blueprint $table) {
            $table->dropForeign('main_product_translation_language_foreign');
            $table->foreign('language', 'main_product_translation_language_foreign')->references('code')->on('lkp_language')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product', function (Blueprint $table) {
            $table->dropForeign(['main_product_code']);
            $table->dropForeign(['original_price_currency']);
            $table->dropForeign(['rrp_currency']);
        });

        Schema::table('lkp_category_translation', function (Blueprint $table) {
            $table->dropForeign(['language']);
            $table->foreign('language')->references('code')->on('lkp_language');
        });

        Schema::table('main_product_translation', function (Blueprint $table) {
            $table->dropForeign(['language']);
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }
};
