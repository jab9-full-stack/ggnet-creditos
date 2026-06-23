<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'agency_id',
    'client_id',
    'credit_request_id',
    'code',
    'status',
    'principal_amount',
    'interest_rate_percent',
    'interest_amount',
    'total_amount',
    'term_weeks',
    'approved_at',
    'disbursed_at',
    'created_by',
    'approved_by',
    'updated_by',
    'notes',
])]
class Credit extends Model
{
    use SoftDeletes;

    public const STATUS_APPROVED_PENDING_DISBURSEMENT = 'approved_pending_disbursement';
    public const STATUS_DISBURSED = 'disbursed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_APPROVED_PENDING_DISBURSEMENT => 'Aprobado pendiente de entrega',
        self::STATUS_DISBURSED => 'Entregado',
        self::STATUS_CANCELLED => 'Cancelado',
        self::STATUS_CLOSED => 'Cerrado',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function creditRequest(): BelongsTo
    {
        return $this->belongsTo(CreditRequest::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
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
            self::STATUS_APPROVED_PENDING_DISBURSEMENT => 'color:var(--warning); font-weight:800;',
            self::STATUS_DISBURSED => 'color:var(--success); font-weight:800;',
            self::STATUS_CANCELLED => 'color:var(--danger); font-weight:800;',
            self::STATUS_CLOSED => 'color:#374151; font-weight:800;',
            default => 'color:#374151; font-weight:800;',
        };
    }

    public function canBeEditedByAdmin(): bool
    {
        return $this->status === self::STATUS_APPROVED_PENDING_DISBURSEMENT;
    }

    protected function casts(): array
    {
        return [
            'principal_amount' => 'decimal:2',
            'interest_rate_percent' => 'decimal:2',
            'interest_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'term_weeks' => 'integer',
            'approved_at' => 'datetime',
            'disbursed_at' => 'datetime',
        ];
    }
}
