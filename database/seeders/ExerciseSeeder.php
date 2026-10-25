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
        'クランチ' => ['腹直筋', 1],
        'プランク' => ['腹直筋', 1],
        'スクワット' => ['大腿四頭筋', 3],
        'レッグカール' => ['ハムストリングス', 2],
        'ヒップスラスト' => ['大臀筋', 2],
        'カーフレイズ' => ['下腿三頭筋', 1],
        'ネックフレクション' => ['胸鎖乳突筋', 1],
        'プッシュアッププラス' => ['前鋸筋', 1],
        'ロシアンツイスト' => ['外腹斜筋', 1],
        'サイドプランク' => ['外腹斜筋', 1],
        'アダクション' => ['内転筋群', 2],
        'トゥレイズ' => ['前脛骨筋', 1],
        'リバースリストカール' => ['前腕伸筋群', 1],
        'リストカール' => ['前腕屈筋群', 1],
        'エクスターナルローテーション' => ['棘下筋', 1],
        'バックエクステンション' => ['脊柱起立筋', 2],
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
