<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'agency_id',
    'client_id',
    'credit_id',
    'code',
    'status',
    'method',
    'reference',
    'installments_count',
    'amount',
    'paid_at',
    'voided_at',
    'notes',
    'received_by',
    'voided_by',
    'void_reason',
    'created_by',
    'updated_by',
])]
class CreditPayment extends Model
{
    use SoftDeletes;

    public const STATUS_APPLIED = 'applied';
    public const STATUS_VOIDED = 'voided';

    public const STATUSES = [
        self::STATUS_APPLIED => 'Aplicado',
        self::STATUS_VOIDED => 'Anulado',
    ];

    public const METHOD_CASH = 'cash';
    public const METHOD_DEPOSIT = 'deposit';
    public const METHOD_TRANSFER = 'transfer';

    public const METHODS = [
        self::METHOD_CASH => 'Efectivo',
        self::METHOD_DEPOSIT => 'Depósito',
        self::METHOD_TRANSFER => 'Transferencia',
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

    public function installments(): HasMany
    {
        return $this->hasMany(CreditInstallment::class);
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_APPLIED => 'color:var(--success); font-weight:800;',
            self::STATUS_VOIDED => 'color:var(--danger); font-weight:800;',
            default => 'color:#374151; font-weight:800;',
        };
    }

    public function canBeVoided(): bool
    {
        return $this->status === self::STATUS_APPLIED;
    }

    protected function casts(): array
    {
        return [
            'installments_count' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }
}
