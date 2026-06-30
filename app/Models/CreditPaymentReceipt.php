<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditPaymentReceipt extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_VOIDED = 'voided';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Vigente',
        self::STATUS_VOIDED => 'Anulado',
    ];

    protected $fillable = [
        'agency_id',
        'client_id',
        'credit_id',
        'credit_payment_id',
        'cash_session_id',
        'cash_movement_id',
        'code',
        'status',
        'method',
        'reference',
        'installments_count',
        'installments_snapshot',
        'amount',
        'issued_at',
        'issued_by',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'installments_count' => 'integer',
            'installments_snapshot' => 'array',
            'amount' => 'decimal:2',
            'issued_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

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

    public function payment(): BelongsTo
    {
        return $this->belongsTo(CreditPayment::class, 'credit_payment_id');
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function cashMovement(): BelongsTo
    {
        return $this->belongsTo(CashMovement::class);
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
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

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'color:var(--success); font-weight:800;',
            self::STATUS_VOIDED => 'color:var(--danger); font-weight:800;',
            default => 'color:#374151; font-weight:800;',
        };
    }

    public function methodLabel(): string
    {
        return CreditPayment::METHODS[$this->method] ?? $this->method;
    }
}
