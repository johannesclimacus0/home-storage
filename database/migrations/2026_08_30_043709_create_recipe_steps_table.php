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
        Schema::create('recipe_steps', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('recipe_id')->constrained('recipes')->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->text('description');
            $table->timestamps();

            $table->unique(['recipe_id', 'position']);

        });
        DB::statement(<<<'SQL'
                ALTER TABLE recipe_steps
                ADD CONSTRAINT recipe_step_position_check
                CHECK (position > 0);
            SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipe_steps');
    }
};
