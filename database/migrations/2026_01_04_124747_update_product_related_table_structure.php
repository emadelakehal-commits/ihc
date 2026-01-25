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
        // Since the table is empty (truncated), we can recreate it with new schema
        Schema::dropIfExists('product_related');

        Schema::create('product_related', function (Blueprint $table) {
            $table->id();
            $table->enum('from_entity_type', ['product', 'product_item']);
            $table->string('from_entity_code', 100);
            $table->enum('to_entity_type', ['product', 'product_item']);
            $table->string('to_entity_code', 100);
            $table->string('relation_type', 50)->nullable();
            $table->timestamps();

            // Add indexes for performance
            $table->index(['from_entity_type', 'from_entity_code']);
            $table->index(['to_entity_type', 'to_entity_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_related');

        // Recreate with old schema
        Schema::create('product_related', function (Blueprint $table) {
            $table->string('product_code', 100);
            $table->string('related_product_code', 100);
            $table->timestamps();

            $table->primary(['product_code', 'related_product_code']);
        });
    }
};
