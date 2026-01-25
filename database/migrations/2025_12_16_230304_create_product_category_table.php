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
        Schema::create('product_category', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->unsignedBigInteger('category_id');
            $table->primary(['product_code', 'category_id']);
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('lkp_category')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_category');
    }
};
