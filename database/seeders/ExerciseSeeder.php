<?php

namespace Database\Seeders;

use App\Models\Exercise;
use App\Models\Muscle;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    /**
     * 種目名 => [主に鍛える筋肉, 推奨休憩日数]
     */
    public const EXERCISES = [
        'ベンチプレス' => ['大胸筋', 2],
        '腕立て伏せ' => ['大胸筋', 1],
        '懸垂' => ['広背筋', 2],
        'ラットプルダウン' => ['広背筋', 2],
        'シュラッグ' => ['僧帽筋', 2],
        'ショルダープレス' => ['三角筋', 2],
        'サイドレイズ' => ['三角筋', 1],
        'アームカール' => ['上腕二頭筋', 2],
        'フレンチプレス' => ['上腕三頭筋', 2],
        'クランチ' => ['腹筋', 1],
        'プランク' => ['腹筋', 1],
        'スクワット' => ['大腿四頭筋', 3],
        'レッグカール' => ['ハムストリングス', 2],
        'ヒップスラスト' => ['大臀筋', 2],
        'カーフレイズ' => ['下腿三頭筋', 1],
    ];

    public function run(): void
    {
        $muscleIds = Muscle::pluck('id', 'name');

        foreach (self::EXERCISES as $name => [$muscleName, $restDays]) {
            Exercise::updateOrCreate(
                ['name' => $name],
                ['muscle_id' => $muscleIds[$muscleName], 'default_rest_days' => $restDays],
            );
        }
    }
}
