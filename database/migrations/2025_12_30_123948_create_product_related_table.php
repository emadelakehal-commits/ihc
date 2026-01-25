 b<?php

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
        Schema::create('product_related', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->string('related_product_code', 100);
            $table->timestamps();

            $table->primary(['product_code', 'related_product_code']);
            // Skip foreign keys for now to avoid constraint issues
            // They can be added later if needed
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_related');
    }
};
