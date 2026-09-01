<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('household_notes')->delete();

        Schema::table('household_notes', function (Blueprint $table) {
            $table->dropForeign(['household_id']);
            $table->dropIndex(['household_id', 'is_pinned', 'created_at']);
            $table->dropColumn(['household_id', 'title', 'is_pinned']);
            $table->foreignId('recipe_id')
                ->after('uuid')
                ->constrained('recipes')
                ->cascadeOnDelete();
            $table->index(['recipe_id', 'author_id', 'created_at']);
        });

        Schema::rename('household_notes', 'recipe_notes');
    }

    public function down(): void
    {
        DB::table('recipe_notes')->delete();

        Schema::rename('recipe_notes', 'household_notes');

        Schema::table('household_notes', function (Blueprint $table) {
            $table->dropForeign(['recipe_id']);
            $table->dropIndex(['recipe_id', 'author_id', 'created_at']);
            $table->dropColumn('recipe_id');
            $table->foreignId('household_id')
                ->after('uuid')
                ->constrained('households')
                ->cascadeOnDelete();
            $table->string('title', 120);
            $table->boolean('is_pinned')->default(false);
            $table->index(['household_id', 'is_pinned', 'created_at']);
        });
    }
};
