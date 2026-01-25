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
        // First, drop all foreign key constraints that reference product_item
        $this->dropForeignKeys();

        // Drop the existing table with wrong schema
        Schema::dropIfExists('product_item');

        // Recreate with correct schema
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

    private function dropForeignKeys()
    {
        $foreignKeyMappings = [
            'product_attribute_value' => 'product_attribute_value_product_code_foreign',
            'product_category' => 'product_category_product_code_foreign',
            'product_delivery' => 'product_delivery_product_code_foreign',
            'product_document' => 'product_document_product_code_foreign',
            'product_item_tag' => 'product_item_tag_product_item_code_foreign',
            'product_item_translation' => 'product_item_translations_product_item_code_foreign',
            'product_tag' => 'product_tag_product_item_code_foreign'
        ];

        foreach ($foreignKeyMappings as $table => $fk) {
            try {
                Schema::table($table, function (Blueprint $tableBlueprint) use ($fk) {
                    $tableBlueprint->dropForeign($fk);
                });
            } catch (\Exception $e) {
                // Foreign key might not exist, continue
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item');
    }
};
