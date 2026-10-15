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
}
