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
            $table->enum('availability', ['s', 'o'])->default('s')->after('rrp_currency')
                ->comment('s = stock, o = on demand');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_item', function (Blueprint $table) {
            $table->dropColumn('availability');
        });
    }
};
