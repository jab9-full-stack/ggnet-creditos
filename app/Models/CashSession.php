<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CashSession extends Model
{
    use SoftDeletes;

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    public const STATUSES = [
        self::STATUS_OPEN => 'Abierta',
        self::STATUS_CLOSED => 'Cerrada',
    ];

    protected $fillable = [
        'agency_id',
        'user_id',
        'code',
        'status',
        'opening_balance',
        'opened_at',
        'opened_by',
        'expected_cash_amount',
        'counted_cash_amount',
        'difference_amount',
        'closed_at',
        'closed_by',
        'opening_notes',
        'closing_notes',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance' => 'decimal:2',
            'expected_cash_amount' => 'decimal:2',
            'counted_cash_amount' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CLOSED);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function openedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(CashMovement::class);
    }

    public function activeMovements(): HasMany
    {
        return $this->movements()->where('status', CashMovement::STATUS_ACTIVE);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function statusStyle(): string
    {
        return match ($this->status) {
            self::STATUS_OPEN => 'color:#166534; font-weight:800;',
            self::STATUS_CLOSED => 'color:#334155; font-weight:800;',
            default => 'color:#475569; font-weight:800;',
        };
    }

    public function expectedCashAmount(): float
    {
        $movementsTotal = $this->activeMovements()
            ->get()
            ->sum(fn (CashMovement $movement): float => $movement->signedAmount());

        return round((float) $this->opening_balance + (float) $movementsTotal, 2);
    }

    public function canBeClosed(): bool
    {
        return $this->status === self::STATUS_OPEN;
    }
}
