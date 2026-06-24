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
    'method',
    'reference',
    'installments_count',
    'amount',
    'paid_at',
    'notes',
    'received_by',
    'created_by',
    'updated_by',
])]
class CreditPayment extends Model
{
    use SoftDeletes;

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

    protected function casts(): array
    {
        return [
            'installments_count' => 'integer',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }
}
