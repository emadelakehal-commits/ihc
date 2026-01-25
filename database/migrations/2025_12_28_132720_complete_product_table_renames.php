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
        // Rename product table to product_item
        Schema::rename('product', 'product_item');

        // Add foreign key constraints for the renamed tables (skip if they already exist)
        try {
            Schema::table('product_translation', function (Blueprint $table) {
                $table->foreign('product_code', 'product_translation_product_code_foreign')->references('product_code')->on('product')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }

        try {
            Schema::table('product_item', function (Blueprint $table) {
                $table->foreign('product_code', 'product_item_product_code_foreign')->references('product_code')->on('product')->onDelete('set null');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }

        try {
            Schema::table('product_attribute_value', function (Blueprint $table) {
                $table->foreign('product_code', 'product_attribute_value_product_code_foreign')->references('product_item_code')->on('product_item')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }

        try {
            Schema::table('product_category', function (Blueprint $table) {
                $table->foreign('product_code', 'product_category_product_code_foreign')->references('product_item_code')->on('product_item')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }

        try {
            Schema::table('product_delivery', function (Blueprint $table) {
                $table->foreign('product_code', 'product_delivery_product_code_foreign')->references('product_item_code')->on('product_item')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }

        try {
            Schema::table('product_document', function (Blueprint $table) {
                $table->foreign('product_code', 'product_document_product_code_foreign')->references('product_item_code')->on('product_item')->onDelete('cascade');
            });
        } catch (\Exception $e) {
            // Constraint might already exist
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the foreign key constraints
        Schema::table('product_translation', function (Blueprint $table) {
            $table->dropForeign('product_translation_product_code_foreign');
        });

        Schema::table('product_item', function (Blueprint $table) {
            $table->dropForeign('product_item_product_code_foreign');
        });

        Schema::table('product_attribute_value', function (Blueprint $table) {
            $table->dropForeign('product_attribute_value_product_code_foreign');
        });

        Schema::table('product_category', function (Blueprint $table) {
            $table->dropForeign('product_category_product_code_foreign');
        });

        Schema::table('product_delivery', function (Blueprint $table) {
            $table->dropForeign('product_delivery_product_code_foreign');
        });

        Schema::table('product_document', function (Blueprint $table) {
            $table->dropForeign('product_document_product_code_foreign');
        });
    }
};
