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
        Schema::table('product_item_translation', function (Blueprint $table) {
            $table->text('variation_text')->nullable()->after('short_desc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_item_translation', function (Blueprint $table) {
            $table->dropColumn('variation_text');
        });
    }
};
