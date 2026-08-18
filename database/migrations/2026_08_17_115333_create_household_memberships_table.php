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
        Schema::create('household_memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('role', 20);
            $table->timestamps();

            $table->unique(['household_id', 'user_id']);
        });

        DB::statement(<<<'SQL'
            ALTER TABLE household_memberships
            ADD CONSTRAINT household_memberships_role_check
            CHECK (role IN ('owner', 'member'))
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX household_memberships_one_owner_per_household
            ON household_memberships (household_id)
            WHERE role = 'owner'
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('household_memberships');
    }
};
