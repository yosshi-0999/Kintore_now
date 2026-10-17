<?php

namespace Database\Seeders;

use App\Models\Muscle;
use Illuminate\Database\Seeder;

class MuscleSeeder extends Seeder
{
    public const NAMES = [
        '大胸筋',
        '広背筋',
        '僧帽筋',
        '三角筋',
        '上腕二頭筋',
        '上腕三頭筋',
        '腹直筋',
        '大腿四頭筋',
        'ハムストリングス',
        '大臀筋',
        '下腿三頭筋',
        '胸鎖乳突筋',
        '前鋸筋',
        '外腹斜筋',
        '内転筋群',
        '前脛骨筋',
        '前腕伸筋群',
        '前腕屈筋群',
        '棘下筋',
        '脊柱起立筋',
    ];

    public function run(): void
    {
        if (! Muscle::where('name', '腹直筋')->exists()) {
            Muscle::where('name', '腹筋')->update(['name' => '腹直筋']);
        }

        foreach (self::NAMES as $name) {
            Muscle::firstOrCreate(['name' => $name]);
        }
    }
}
