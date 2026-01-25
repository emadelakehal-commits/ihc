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
        Schema::create('product_delivery', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->string('domain_id', 50);
            $table->integer('delivery_min');
            $table->integer('delivery_max');
            $table->primary(['product_code', 'domain_id']);
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('domain_id')->references('code')->on('domain')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_delivery');
    }
};
