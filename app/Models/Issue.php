<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Issue extends Model
{
    protected $guarded = [];

    protected $casts = [
        'labels' => 'array',
        'last_activity_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
