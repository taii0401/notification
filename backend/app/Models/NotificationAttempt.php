<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'delivery_id',
    'attempt_no',
    'status',
    'request_payload',
    'response_code',
    'response_body',
    'error_type',
    'error_message',
    'started_at',
    'finished_at',
])]
class NotificationAttempt extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'attempt_no' => 'integer',
            'request_payload' => 'array',
            'response_code' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(NotificationDelivery::class, 'delivery_id');
    }
}
