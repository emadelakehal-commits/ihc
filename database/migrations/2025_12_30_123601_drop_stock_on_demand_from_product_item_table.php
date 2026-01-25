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
        Schema::table('product_item', function (Blueprint $table) {
            $table->dropColumn('stock_on_demand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_item', function (Blueprint $table) {
            $table->enum('stock_on_demand', ['S', 'O'])->default('S')->after('isku');
        });
    }
};
