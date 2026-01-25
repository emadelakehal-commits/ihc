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
        Schema::create('product_translation', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->string('language', 10);
            $table->string('title', 255);
            $table->string('slogan', 255)->nullable();
            $table->text('summary')->nullable();
            $table->longText('description')->nullable();
            $table->primary(['product_code', 'language']);
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_translation');
    }
};
