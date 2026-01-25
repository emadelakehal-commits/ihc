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
        Schema::create('lkp_category_translation', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id');
            $table->string('language', 10);
            $table->string('name', 255);
            $table->primary(['category_id', 'language']);
            $table->foreign('category_id')->references('id')->on('lkp_category')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lkp_category_translation');
    }
};
