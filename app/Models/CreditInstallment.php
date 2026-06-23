<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'agency_id',
    'client_id',
    'credit_id',
    'number',
    'status',
    'due_date',
    'principal_amount',
    'interest_amount',
    'total_amount',
    'paid_amount',
    'paid_at',
    'notes',
    'created_by',
    'updated_by',
])]
class CreditInstallment extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pendiente',
        self::STATUS_PAID => 'Pagada',
        self::STATUS_OVERDUE => 'Vencida',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function credit(): BelongsTo
    {
        return $this->belongsTo(Credit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_PENDING => 'color:var(--warning); font-weight:800;',
            self::STATUS_PAID => 'color:var(--success); font-weight:800;',
            self::STATUS_OVERDUE => 'color:var(--danger); font-weight:800;',
            self::STATUS_CANCELLED => 'color:#6b7280; font-weight:800;',
            default => 'color:#374151; font-weight:800;',
        };
    }

    protected function casts(): array
    {
        return [
            'number' => 'integer',
            'due_date' => 'date',
            'principal_amount' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
