<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

#[Fillable([
    'project_id',
    'name',
    'key_prefix',
    'key_hash',
    'status',
    'last_used_at',
    'expires_at',
])]
class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

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
}
