<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Carbon;

class Workout extends Model
{
    /** @use HasFactory<WorkoutFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'performed_at',
        'menu_name',
        'weight',
        'reps',
        'sets',
        'rest_days',
    ];

    protected function casts(): array
    {
        return [
            'performed_at' => 'datetime',
            'weight' => 'float',
            'reps' => 'integer',
            'sets' => 'integer',
            'rest_days' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function muscles(): BelongsToMany
    {
        return $this->belongsToMany(Muscle::class);
    }

    /**
     * 記録の検索（空の条件は無視する）
     *
     * @param  array{keyword?: ?string, muscle_id?: int|string|null, from?: ?string, to?: ?string}  $filters
     */
    public function scopeSearch(Builder $query, array $filters): void
    {
        if (filled($filters['keyword'] ?? null)) {
            // % と _ をワイルドカードとして扱わないようにエスケープする
            $keyword = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['keyword']);
            $query->where('menu_name', 'like', '%'.$keyword.'%');
        }

        if (filled($filters['muscle_id'] ?? null)) {
            $query->whereHas('muscles', fn (Builder $muscles) => $muscles->whereKey($filters['muscle_id']));
        }

        if (filled($filters['from'] ?? null)) {
            $query->where('performed_at', '>=', Carbon::parse($filters['from'])->startOfDay());
        }

        if (filled($filters['to'] ?? null)) {
            $query->where('performed_at', '<=', Carbon::parse($filters['to'])->endOfDay());
        }
    }
}
