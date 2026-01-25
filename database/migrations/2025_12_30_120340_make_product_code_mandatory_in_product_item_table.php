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
            // Make product_code not nullable (foreign key was already handled)
            $table->string('product_code', 100)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_item', function (Blueprint $table) {
            // Drop the mandatory foreign key
            $table->dropForeign('product_item_product_code_foreign');

            // Make product_code nullable again and recreate the foreign key
            $table->string('product_code', 100)->nullable()->change();
            $table->foreign('product_code', 'product_item_product_code_foreign')->references('product_code')->on('product')->onDelete('set null');
        });
    }
};
