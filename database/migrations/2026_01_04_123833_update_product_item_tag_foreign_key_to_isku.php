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
        Schema::dropIfExists('product_item_tag');

        Schema::create('product_item_tag', function (Blueprint $table) {
            $table->string('isku', 100);
            $table->string('item_tag_code', 50);
            $table->timestamps();

            $table->primary(['isku', 'item_tag_code']);
            $table->foreign('isku')->references('isku')->on('product_item')->onDelete('cascade');
            $table->foreign('item_tag_code')->references('item_tag_code')->on('lkp_item_tag')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_tag');

        Schema::create('product_item_tag', function (Blueprint $table) {
            $table->string('product_item_code', 100);
            $table->string('item_tag_code', 50);
            $table->timestamps();

            $table->primary(['product_item_code', 'item_tag_code']);
        });
    }
};
