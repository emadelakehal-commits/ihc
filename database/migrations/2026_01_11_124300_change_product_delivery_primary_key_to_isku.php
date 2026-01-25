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
        // Since the product_code column is wrong and should be removed,
        // we'll recreate the table with the correct structure
        DB::statement('DROP TABLE IF EXISTS product_delivery');

        DB::statement('CREATE TABLE product_delivery (
            isku VARCHAR(100) NOT NULL,
            domain_id VARCHAR(50) NOT NULL,
            delivery_min INT NOT NULL,
            delivery_max INT NOT NULL,
            PRIMARY KEY (isku),
            KEY idx_delivery_domain (domain_id),
            CONSTRAINT FK_product_delivery_domain FOREIGN KEY (domain_id) REFERENCES domain (code) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_delivery', function (Blueprint $table) {
            // Drop the current primary key
            $table->dropPrimary();

            // Add back the product_code column
            $table->string('product_code', 100)->after('id');

            // Restore the old primary key
            $table->primary(['product_code', 'domain_id']);
        });
    }
};
