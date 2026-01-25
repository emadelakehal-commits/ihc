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
        Schema::dropIfExists('product_tag');

        Schema::create('product_tag', function (Blueprint $table) {
            $table->string('product_code', 255);
            $table->string('tag_code', 50);
            $table->timestamps();

            $table->primary(['product_code', 'tag_code']);
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('tag_code')->references('tag_code')->on('lkp_tag')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_tag');

        Schema::create('product_tag', function (Blueprint $table) {
            $table->string('product_item_code', 100);
            $table->string('tag_code', 50);
            $table->timestamps();

            $table->primary(['product_item_code', 'tag_code']);
        });
    }
};
