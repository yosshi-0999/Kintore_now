<?php

namespace App\Models;

use Database\Factories\MuscleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Muscle extends Model
{
    /** @use HasFactory<MuscleFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'name',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function workouts(): BelongsToMany
    {
        return $this->belongsToMany(Workout::class);
    }
}
