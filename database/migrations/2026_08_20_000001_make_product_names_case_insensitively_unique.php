<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropUnique(['name']);
        });

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX products_name_lower_unique
            ON products (LOWER(name))
        SQL);
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS products_name_lower_unique');

        Schema::table('products', function (Blueprint $table): void {
            $table->unique('name');
        });
    }
};
