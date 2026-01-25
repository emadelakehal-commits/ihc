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
        // Change primary key from product_item_code to isku
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // Drop existing primary key
        DB::statement('ALTER TABLE product_item DROP PRIMARY KEY');

        // Make isku NOT NULL and add primary key
        DB::statement('ALTER TABLE product_item MODIFY isku VARCHAR(100) NOT NULL');
        DB::statement('ALTER TABLE product_item ADD PRIMARY KEY (isku)');

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item');

        Schema::create('product_item', function (Blueprint $table) {
            $table->string('product_item_code', 100)->primary();
            $table->string('product_code', 100);
            $table->string('isku', 100)->nullable();
            $table->boolean('is_active')->default(true);
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('cost_currency', 3)->nullable();
            $table->decimal('rrp', 10, 2)->nullable();
            $table->string('rrp_currency', 3)->nullable();
            $table->timestamps();

            // Foreign key to product table
            $table->foreign('product_code')->references('product_code')->on('product');
        });
    }
};
