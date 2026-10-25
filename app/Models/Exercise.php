<?php

namespace App\Models;

use Database\Factories\ExerciseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Exercise extends Model
{
    /** @use HasFactory<ExerciseFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
        'muscle_id',
        'default_rest_days',
    ];

    public function muscle(): BelongsTo
    {
        return $this->belongsTo(Muscle::class);
    }

    /**
     * メニュー名と完全に一致する種目（前後の空白は無視する）。
     * MySQL の照合順序では「ハ」「バ」「パ」なども同じ文字として比べるため、最後は PHP で厳密に比べる。
     */
    public static function findByExactName(?string $name): ?self
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        return static::where('name', $name)->get()->first(fn (self $exercise) => $exercise->name === $name);
    }
}
