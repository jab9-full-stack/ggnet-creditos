<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashMovement extends Model
{
    use SoftDeletes;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_VOIDED = 'voided';

    public const TYPE_CREDIT_PAYMENT = 'credit_payment';
    public const TYPE_MANUAL_EXPENSE = 'manual_expense';
    public const TYPE_ADJUSTMENT_IN = 'adjustment_in';
    public const TYPE_ADJUSTMENT_OUT = 'adjustment_out';

    public const METHOD_CASH = 'cash';
    public const METHOD_DEPOSIT = 'deposit';
    public const METHOD_TRANSFER = 'transfer';

    public const STATUSES = [
        self::STATUS_ACTIVE => 'Activo',
        self::STATUS_VOIDED => 'Anulado',
    ];

    public const TYPES = [
        self::TYPE_CREDIT_PAYMENT => 'Ingreso por pago de crédito',
        self::TYPE_MANUAL_EXPENSE => 'Egreso manual autorizado',
        self::TYPE_ADJUSTMENT_IN => 'Ajuste positivo autorizado',
        self::TYPE_ADJUSTMENT_OUT => 'Ajuste negativo autorizado',
    ];

    public const MANUAL_TYPES = [
        self::TYPE_MANUAL_EXPENSE => 'Egreso manual autorizado',
        self::TYPE_ADJUSTMENT_IN => 'Ajuste positivo autorizado',
        self::TYPE_ADJUSTMENT_OUT => 'Ajuste negativo autorizado',
    ];

    public const METHODS = [
        self::METHOD_CASH => 'Efectivo',
        self::METHOD_DEPOSIT => 'Depósito',
        self::METHOD_TRANSFER => 'Transferencia',
    ];

    protected $fillable = [
        'cash_session_id',
        'agency_id',
        'client_id',
        'credit_id',
        'credit_payment_id',
        'code',
        'status',
        'type',
        'method',
        'amount',
        'reference',
        'description',
        'movement_at',
        'voided_at',
        'voided_by',
        'void_reason',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'movement_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
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

    public function creditPayment(): BelongsTo
    {
        return $this->belongsTo(CreditPayment::class);
    }

    public function receipt(): HasOne
    {
        return $this->hasOne(CreditPaymentReceipt::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function methodLabel(): string
    {
        return self::METHODS[$this->method] ?? $this->method;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function signedAmount(): float
    {
        $amount = round((float) $this->amount, 2);

        return match ($this->type) {
            self::TYPE_MANUAL_EXPENSE,
            self::TYPE_ADJUSTMENT_OUT => $amount * -1,
            default => $amount,
        };
    }

    public function canBeVoided(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_ACTIVE => 'color:#166534; font-weight:800;',
            self::STATUS_VOIDED => 'color:#b91c1c; font-weight:800;',
            default => 'color:#475569; font-weight:800;',
        };
    }
}
