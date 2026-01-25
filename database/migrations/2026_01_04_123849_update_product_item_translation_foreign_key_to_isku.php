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
        Schema::dropIfExists('product_item_translation');

        Schema::create('product_item_translations', function (Blueprint $table) {
            $table->string('isku', 100);
            $table->string('language', 10);
            $table->string('title', 255);
            $table->text('short_desc')->nullable();
            $table->timestamps();

            $table->primary(['isku', 'language']);
            $table->foreign('isku')->references('isku')->on('product_item')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_translations');

        Schema::create('product_item_translations', function (Blueprint $table) {
            $table->string('product_item_code', 100);
            $table->string('language', 10);
            $table->string('title', 255);
            $table->text('short_desc')->nullable();
            $table->timestamps();

            $table->primary(['product_item_code', 'language']);
        });
    }
};
