<?php

namespace App\Models;

use Database\Factories\WorkoutPlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WorkoutPlan extends Model
{
    /** @use HasFactory<WorkoutPlanFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'scheduled_at',
        'place',
        'menu_name',
        'is_enabled',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
            'is_enabled' => 'boolean',
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
     * 通知を送るべきプラン（有効・未送信・予定日時が現在以前）
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('is_enabled', true)
            ->whereNull('sent_at')
            ->where('scheduled_at', '<=', now());
    }
}
