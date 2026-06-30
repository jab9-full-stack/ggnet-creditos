<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'agency_id',
    'code',
    'first_name',
    'middle_name',
    'last_name',
    'second_last_name',
    'married_name',
    'dpi',
    'nit',
    'birth_date',
    'gender',
    'phone',
    'secondary_phone',
    'email',
    'address_line',
    'city',
    'department',
    'country',
    'occupation',
    'workplace',
    'status',
    'notes',
    'created_by',
    'updated_by',
])]
class Client extends Model
{
    use SoftDeletes;

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }


    public function references(): HasMany
    {
        return $this->hasMany(ClientReference::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ClientDocument::class);
    }

    public function creditRequests(): HasMany
    {
        return $this->hasMany(CreditRequest::class);
    }

    public function credits(): HasMany
    {
        return $this->hasMany(Credit::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function fullName(): string
    {
        return collect([
            $this->first_name,
            $this->middle_name,
            $this->last_name,
            $this->second_last_name,
            $this->married_name,
        ])
            ->filter()
            ->implode(' ');
    }


    public function isCreditBlocked(): bool
    {
        return (bool) $this->credit_blocked_at;
    }

    public function creditBlockLabel(): string
    {
        return $this->isCreditBlocked()
            ? 'Bloqueado para nuevo crédito'
            : 'Habilitado para nuevo crédito';
    }

    protected function casts(): array
    {
        return [
            'credit_blocked_at' => 'datetime',
            'credit_last_delinquency_at' => 'datetime',
            'birth_date' => 'date',
        ];
    }
}
