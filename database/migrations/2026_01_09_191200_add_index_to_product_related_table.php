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
        Schema::table('product_related', function (Blueprint $table) {
            $table->index(['relation_type', 'from_entity_type', 'from_entity_code', 'to_entity_type', 'to_entity_code'], 'idx_related_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_related', function (Blueprint $table) {
            $table->dropIndex('idx_related_lookup');
        });
    }
};
