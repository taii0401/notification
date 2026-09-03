<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'project_id',
    'template_id',
    'event_type',
    'channel',
    'recipient',
    'payload',
    'metadata',
    'status',
    'scheduled_at',
    'processed_at',
    'sent_at',
    'failed_at',
])]
class Notification extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /** @return array<int, string> */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'metadata' => 'array',
            'scheduled_at' => 'datetime',
            'processed_at' => 'datetime',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(NotificationDelivery::class);
    }

    public function idempotencyKeys(): HasMany
    {
        return $this->hasMany(IdempotencyKey::class);
    }
}
