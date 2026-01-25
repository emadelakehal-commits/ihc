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
            // Only rename if the old columns exist
            if (Schema::hasColumn('product_item', 'original_price')) {
                $table->renameColumn('original_price', 'cost');
            }
            if (Schema::hasColumn('product_item', 'original_price_currency')) {
                $table->renameColumn('original_price_currency', 'cost_currency');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_item', function (Blueprint $table) {
            $table->renameColumn('cost', 'original_price');
            $table->renameColumn('cost_currency', 'original_price_currency');
        });
    }
};
