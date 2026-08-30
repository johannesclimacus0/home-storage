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
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('servings')->default(1);
            $table->unsignedSmallInteger('before_cooking_minutes')->default(0);
            $table->unsignedSmallInteger('cooking_minutes')->default(0);
            $table->timestamps();

            $table->index('created_by_user_id');
            $table->index('title');

        });
        DB::statement(<<<'SQL'
                ALTER TABLE recipes
                ADD CONSTRAINT recipe_servings_amount_check
                CHECK (servings > 0);
            SQL);
        DB::statement(<<<'SQL'
                ALTER TABLE recipes
                ADD CONSTRAINT recipe_before_cooking_minutes_amount_check
                CHECK (before_cooking_minutes >= 0);
            SQL);
        DB::statement(<<<'SQL'
                ALTER TABLE recipes
                ADD CONSTRAINT recipe_cooking_minutes_amount_check
                CHECK (cooking_minutes >= 0);
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
