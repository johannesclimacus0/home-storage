<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopping_list_items', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('household_id')->constrained('households')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('added_by_user_id')->constrained('users')->restrictOnDelete();
            $table->decimal('quantity', 15, 3);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['household_id', 'product_id'], 'unique_shopping_list_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopping_list_items');
    }
};
