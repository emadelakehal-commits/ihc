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
        Schema::create('product', function (Blueprint $table) {
            $table->string('product_code', 100)->primary();
            $table->string('main_product_code', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('original_price', 10, 2)->nullable();
            $table->string('original_price_currency', 3)->nullable();
            $table->decimal('rrp', 10, 2)->nullable();
            $table->string('rrp_currency', 3)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product');
    }
};
