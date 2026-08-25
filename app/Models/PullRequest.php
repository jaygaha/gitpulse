<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PullRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'checks_status' => 'array',
        'last_activity_at' => 'datetime',
        'merged_at' => 'datetime',
    ];

    public function repository(): BelongsTo
    {
        return $this->belongsTo(Repository::class);
    }
}
