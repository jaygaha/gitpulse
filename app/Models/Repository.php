<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Repository extends Model
{
    protected $guarded = [];

    protected $casts = [
        'private' => 'boolean',
        'archived' => 'boolean',
        'stale_threshold_days' => 'integer',
        'last_scanned_at' => 'datetime',
    ];

    public function issues(): HasMany
    {
        return $this->hasMany(Issue::class);
    }

    public function pullRequests(): HasMany
    {
        return $this->hasMany(PullRequest::class);
    }

    public function securityAlerts(): HasMany
    {
        return $this->hasMany(SecurityAlert::class);
    }
}
