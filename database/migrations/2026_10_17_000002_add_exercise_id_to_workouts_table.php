<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 種目を選ばずに記録したときは NULL。種目が消されても記録は残す
        Schema::table('workouts', function (Blueprint $table) {
            $table->foreignId('exercise_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        // 追加前の記録は、メニュー名が種目名と完全に一致するものだけ紐付ける。
        // MySQL の照合順序は「ハ」「バ」「パ」などを同じ文字として比べるため、PHP の配列キーで厳密に比べる
        // （Exercise::findByExactName と同じ基準。後でモデルが変わっても結果が変わらないよう、ここではモデルを使わない）
        $exerciseIds = DB::table('exercises')->pluck('id', 'name')->all();

        DB::table('workouts')->whereNull('exercise_id')->select('id', 'menu_name')->orderBy('id')
            ->each(function (object $workout) use ($exerciseIds) {
                $exerciseId = $exerciseIds[trim($workout->menu_name)] ?? null;

                if ($exerciseId !== null) {
                    DB::table('workouts')->where('id', $workout->id)->update(['exercise_id' => $exerciseId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('workouts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('exercise_id');
        });
    }
};
