<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('muscle_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('default_rest_days');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
