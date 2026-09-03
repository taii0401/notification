<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'template_id');
    }
}
