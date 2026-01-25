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
        Schema::create('product_item_translations', function (Blueprint $table) {
            $table->string('product_item_code', 100);
            $table->string('language', 10);
            $table->string('title', 255);
            $table->text('short_desc')->nullable();
            $table->timestamps();

            $table->primary(['product_item_code', 'language']);
            // Skip foreign keys for now to avoid constraint issues
            // They can be added later if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_translations');
    }
};
