<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SecurityAlert extends Model
{
    protected $guarded = [];

    protected $casts = [
        'dismissed_at' => 'datetime',
        'fixed_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
