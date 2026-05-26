<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('dashboard', [
            'metrics' => [
                'users' => User::query()->count(),
                'agencies' => Agency::query()->count(),
                'roles' => Role::query()->count(),
                'permissions' => Permission::query()->count(),
                'settings' => Setting::query()->count(),
                'audit_logs' => AuditLog::query()->count(),
            ],
            'recentAuditLogs' => AuditLog::query()
                ->with('user:id,name,email')
                ->latest()
                ->limit(8)
                ->get(),
        ]);
    }
}
