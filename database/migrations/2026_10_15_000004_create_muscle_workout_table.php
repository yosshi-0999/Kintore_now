<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('muscle_workout', function (Blueprint $table) {
            $table->foreignId('muscle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workout_id')->constrained()->cascadeOnDelete();

            $table->primary(['muscle_id', 'workout_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('muscle_workout');
    }
};
