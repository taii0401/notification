<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'project_id',
    'code',
    'channel',
    'name',
    'subject',
    'content',
    'status',
])]
class NotificationTemplate extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = [
        'status_display',
    ];

    protected function statusDisplay(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                'active' => '啟用',
                'inactive' => '停用',
                default => $this->status,
            },
        );
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(NotificationMessage::class, 'template_id');
    }
}
