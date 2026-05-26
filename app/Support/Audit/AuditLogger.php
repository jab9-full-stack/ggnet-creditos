<?php

namespace App\Support\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    public function log(
        string $event,
        ?string $module = null,
        ?Model $auditable = null,
        array $oldValues = [],
        array $newValues = [],
        array $context = [],
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        $resolvedUser = $user;

        if (! $resolvedUser && Auth::check()) {
            $authUser = Auth::user();

            if ($authUser instanceof User) {
                $resolvedUser = $authUser;
            }
        }

        return AuditLog::query()->create([
            'user_id' => $resolvedUser?->id,
            'event' => $event,
            'module' => $module,
            'auditable_type' => $auditable ? $auditable::class : null,
            'auditable_id' => $auditable?->getKey(),
            'old_values' => $oldValues === [] ? null : $oldValues,
            'new_values' => $newValues === [] ? null : $newValues,
            'context' => $context === [] ? null : $context,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public function created(Model $model, ?string $module = null, array $context = [], ?User $user = null): AuditLog
    {
        return $this->log(
            event: 'created',
            module: $module,
            auditable: $model,
            newValues: $model->getAttributes(),
            context: $context,
            user: $user,
        );
    }

    public function updated(Model $model, array $oldValues, array $newValues, ?string $module = null, array $context = [], ?User $user = null): AuditLog
    {
        return $this->log(
            event: 'updated',
            module: $module,
            auditable: $model,
            oldValues: $oldValues,
            newValues: $newValues,
            context: $context,
            user: $user,
        );
    }

    public function deleted(Model $model, ?string $module = null, array $context = [], ?User $user = null): AuditLog
    {
        return $this->log(
            event: 'deleted',
            module: $module,
            auditable: $model,
            oldValues: $model->getAttributes(),
            context: $context,
            user: $user,
        );
    }
}
