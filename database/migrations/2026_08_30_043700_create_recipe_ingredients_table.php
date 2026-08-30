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
        Schema::create('recipe_ingredients', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity', 14, 3);
            $table->unsignedSmallInteger('position');
            $table->boolean('is_optional')->default(false);
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['recipe_id', 'product_id']);
            $table->unique(['recipe_id', 'position']);
            $table->index('product_id');
        });
        DB::statement(<<<'SQL'
                ALTER TABLE recipe_ingredients
                ADD CONSTRAINT ingredient_quantity_check
                CHECK (quantity > 0);
            SQL);
        DB::statement(<<<'SQL'
                ALTER TABLE recipe_ingredients
                ADD CONSTRAINT ingredient_position_check
                CHECK (position > 0);
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_ingredients');
    }
};
