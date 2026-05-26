<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'group',
    'key',
    'value',
    'type',
    'description',
    'is_public',
    'is_locked',
    'updated_by',
])]
class Setting extends Model
{
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'value' => 'array',
            'is_public' => 'boolean',
            'is_locked' => 'boolean',
        ];
    }
}
