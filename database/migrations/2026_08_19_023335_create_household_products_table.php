<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('household_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('low_stock_threshold', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['household_id', 'product_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE household_products
            ADD CONSTRAINT household_products_low_stock_threshold_check
            CHECK (low_stock_threshold >= 0)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_products');
    }
};
