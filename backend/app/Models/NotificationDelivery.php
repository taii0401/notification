<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'notification_id',
    'provider',
    'status',
    'attempt_count',
    'provider_message_id',
    'last_error',
    'sent_at',
    'failed_at',
])]
class NotificationDelivery extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'sent_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(Notification::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(NotificationAttempt::class, 'delivery_id');
    }
}
