<?php

namespace App\Models;

use Database\Factories\WorkoutFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
}
