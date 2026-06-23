<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'agency_id',
    'client_id',
    'code',
    'status',
    'requested_amount',
    'requested_term_weeks',
    'purpose',
    'income_source',
    'monthly_income',
    'notes',
    'review_notes',
    'decision_notes',
    'submitted_at',
    'reviewed_at',
    'approved_at',
    'rejected_at',
    'cancelled_at',
    'created_by',
    'updated_by',
    'reviewed_by',
    'approved_by',
    'rejected_by',
    'cancelled_by',
])]
class CreditRequest extends Model
{
    use SoftDeletes;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT => 'Borrador',
        self::STATUS_SUBMITTED => 'Enviada',
        self::STATUS_IN_REVIEW => 'En revisión',
        self::STATUS_APPROVED => 'Aprobada',
        self::STATUS_REJECTED => 'Rechazada',
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

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function credit(): HasOne
    {
        return $this->hasOne(Credit::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'color:#374151; font-weight:800;',
            self::STATUS_SUBMITTED => 'color:#1e40af; font-weight:800;',
            self::STATUS_IN_REVIEW => 'color:var(--warning); font-weight:800;',
            self::STATUS_APPROVED => 'color:var(--success); font-weight:800;',
            self::STATUS_REJECTED => 'color:var(--danger); font-weight:800;',
            self::STATUS_CANCELLED => 'color:#6b7280; font-weight:800;',
            default => 'color:#374151; font-weight:800;',
        };
    }

    public function canBeUpdated(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function canBeDeleted(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'requested_term_weeks' => 'integer',
            'monthly_income' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }
}
