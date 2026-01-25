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
        // Since the table is empty (truncated), we can recreate it with new schema
        Schema::dropIfExists('product_attribute_value');

        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->string('isku', 100);
            $table->string('attribute_name', 100);
            $table->string('language', 10);
            $table->string('value', 255);
            $table->timestamps();

            $table->primary(['isku', 'attribute_name', 'language']);
            $table->foreign('isku')->references('isku')->on('product_item')->onDelete('cascade');
            $table->foreign('attribute_name')->references('name')->on('lkp_attribute')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value');

        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->string('product_item_code', 100);
            $table->string('attribute_name', 100);
            $table->string('language', 10);
            $table->string('value', 255);
            $table->timestamps();

            $table->primary(['product_item_code', 'attribute_name', 'language']);
            $table->foreign('product_item_code')->references('product_item_code')->on('product_item')->onDelete('cascade');
            $table->foreign('attribute_name')->references('name')->on('lkp_attribute')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }
};
