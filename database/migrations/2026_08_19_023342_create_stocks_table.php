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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_product_id')->constrained('household_products')->cascadeOnDelete();
            $table->foreignId('storage_location_id')->constrained('storage_locations')->cascadeOnDelete();
            $table->decimal('quantity', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['household_product_id', 'storage_location_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE stocks
            ADD CONSTRAINT stocks_quantity_check
            CHECK (quantity >= 0)
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
