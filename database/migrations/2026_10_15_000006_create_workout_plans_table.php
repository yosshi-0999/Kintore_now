<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->dateTime('scheduled_at');
            $table->string('place');
            $table->string('menu_name');
            $table->boolean('is_enabled')->default(true);
            $table->dateTime('sent_at')->nullable();
            $table->timestamps();

            $table->index(['is_enabled', 'sent_at', 'scheduled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_plans');
    }
};
