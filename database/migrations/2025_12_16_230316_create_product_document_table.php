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
        Schema::create('product_document', function (Blueprint $table) {
            $table->id();
            $table->string('product_code', 100);
            $table->enum('doc_type', ['manual', 'technical', 'warranty']);
            $table->string('file_url', 500);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('product_code')->references('product_code')->on('product')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_document');
    }
};
