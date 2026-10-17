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
        '腹筋',
        '大腿四頭筋',
        'ハムストリングス',
        '大臀筋',
        '下腿三頭筋',
    ];

    public function run(): void
    {
        foreach (self::NAMES as $name) {
            Muscle::firstOrCreate(['name' => $name]);
        }
    }
}
