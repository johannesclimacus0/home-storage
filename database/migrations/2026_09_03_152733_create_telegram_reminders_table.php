<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_reminders', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->timestamp('remind_at');
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamps();

            $table->index(['dispatched_at', 'remind_at']);
            $table->index(['user_id', 'remind_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_reminders');
    }
};
