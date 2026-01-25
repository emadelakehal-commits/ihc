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
        Schema::create('lkp_item_tag_translations', function (Blueprint $table) {
            $table->string('item_tag_code', 50);
            $table->string('language', 10);
            $table->string('name', 100);
            $table->timestamps();

            $table->primary(['item_tag_code', 'language']);
            $table->foreign('item_tag_code')->references('item_tag_code')->on('lkp_item_tag')->onDelete('cascade');
            $table->foreign('language')->references('code')->on('lkp_language')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lkp_item_tag_translations');
    }
};
