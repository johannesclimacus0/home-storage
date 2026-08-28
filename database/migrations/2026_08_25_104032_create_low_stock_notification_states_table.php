<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('low_stock_notification_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('household_membership_id')->constrained()->cascadeOnDelete();
            $table->foreignId('household_product_id')->constrained()->cascadeOnDelete();
            $table->timestamp('last_notified_at');
            $table->timestamps();

            $table->unique(['household_membership_id', 'household_product_id']);
            $table->index('last_notified_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('low_stock_notification_states');
    }
};
