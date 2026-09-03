<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id',
    'notification_id',
    'idempotency_key',
    'request_hash',
    'expires_at',
])]
class IdempotencyKey extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }
}
