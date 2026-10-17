<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViewPermission extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'owner_id',
        'viewer_id',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function viewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'viewer_id');
    }
}
