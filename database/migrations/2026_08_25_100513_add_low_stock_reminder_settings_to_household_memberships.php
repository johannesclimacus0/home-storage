<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('household_memberships', function (Blueprint $table) {
            $table->boolean('low_stock_reminders_enabled')->default(true);
            $table->unsignedSmallInteger('low_stock_reminder_interval_hours')->default(24);
        });
    }

    public function down(): void
    {
        Schema::table('household_memberships', function (Blueprint $table) {
            $table->dropColumn([
                'low_stock_reminders_enabled',
                'low_stock_reminder_interval_hours',
            ]);
        });
    }
};
