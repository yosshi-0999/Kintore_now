<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 追加前の記録には値がないため NULL 可にする（回数・セット数の必須はフォームで担保する）
        Schema::table('workouts', function (Blueprint $table) {
            $table->decimal('weight', 5, 1)->nullable()->after('menu_name');
            $table->unsignedSmallInteger('reps')->nullable()->after('weight');
            $table->unsignedTinyInteger('sets')->nullable()->after('reps');
        });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropColumn(['weight', 'reps', 'sets']);
        });
    }
};
