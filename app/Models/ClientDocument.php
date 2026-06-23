<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'client_id',
    'type',
    'title',
    'original_name',
    'disk',
    'path',
    'mime_type',
    'size_bytes',
    'status',
    'notes',
    'uploaded_by',
    'updated_by',
    'verified_by',
    'verified_at',
])]
class ClientDocument extends Model
{
    use SoftDeletes;

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function readableSize(): string
    {
        if ($this->size_bytes < 1024) {
            return $this->size_bytes.' B';
        }

        if ($this->size_bytes < 1048576) {
            return round($this->size_bytes / 1024, 1).' KB';
        }

        return round($this->size_bytes / 1048576, 1).' MB';
    }

    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
            'verified_at' => 'datetime',
        ];
    }
}
