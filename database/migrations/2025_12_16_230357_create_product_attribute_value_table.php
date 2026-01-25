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
        Schema::create('product_attribute_value', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->string('attribute_name', 100);
            $table->string('value', 255);
            $table->primary(['product_code', 'attribute_name']);
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('attribute_name')->references('name')->on('lkp_attribute')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_attribute_value');
    }
};
