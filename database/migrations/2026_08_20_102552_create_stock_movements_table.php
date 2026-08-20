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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('household_product_id')->nullable()->constrained('household_products')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('storage_location_id')->nullable()->constrained('storage_locations')->nullOnDelete();
            $table->foreignId('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 20);
            $table->decimal('input_quantity', 14, 3);
            $table->string('input_unit', 20);
            $table->decimal('quantity_delta', 14, 3);
            $table->decimal('quantity_before', 14, 3);
            $table->decimal('quantity_after', 14, 3);
            $table->string('product_name');
            $table->string('storage_location_name');
            $table->string('actor_name');
            $table->timestamps();

            $table->index(['household_id', 'created_at']);
            $table->index(['household_id', 'product_id', 'created_at']);
            $table->index(['household_id', 'type', 'created_at']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE stock_movements
            ADD CONSTRAINT stock_movements_type_check
            CHECK (type IN ('purchase', 'consumption', 'adjustment')),
            ADD CONSTRAINT stock_movements_input_unit_check
            CHECK (input_unit IN ('g', 'kg', 'ml', 'l', 'piece')),
            ADD CONSTRAINT stock_movements_input_quantity_check
            CHECK (input_quantity > 0),
            ADD CONSTRAINT stock_movements_quantity_before_check
            CHECK (quantity_before >= 0),
            ADD CONSTRAINT stock_movements_quantity_after_check
            CHECK (quantity_after >= 0),
            ADD CONSTRAINT stock_movements_quantity_delta_check
            CHECK (quantity_delta <> 0),
            ADD CONSTRAINT stock_movements_quantity_equation_check
            CHECK (quantity_after = quantity_before + quantity_delta),
            ADD CONSTRAINT stock_movements_type_delta_check
            CHECK (
                (type = 'purchase' AND quantity_delta > 0)
                OR (type = 'consumption' AND quantity_delta < 0)
                OR (type = 'adjustment' AND quantity_delta <> 0)
            )
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
