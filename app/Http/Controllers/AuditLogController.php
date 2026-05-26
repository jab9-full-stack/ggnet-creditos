<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('audit_logs.view'), 403);

        $module = trim((string) $request->query('module', ''));
        $event = trim((string) $request->query('event', ''));
        $userId = trim((string) $request->query('user_id', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $logs = AuditLog::query()
            ->with('user:id,name,email')
            ->when($module !== '', fn ($query) => $query->where('module', $module))
            ->when($event !== '', fn ($query) => $query->where('event', $event))
            ->when($userId !== '', fn ($query) => $query->where('user_id', $userId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('audit-logs.index', [
            'logs' => $logs,
            'filters' => [
                'module' => $module,
                'event' => $event,
                'user_id' => $userId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'modules' => AuditLog::query()
                ->whereNotNull('module')
                ->distinct()
                ->orderBy('module')
                ->pluck('module'),
            'events' => AuditLog::query()
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
            'users' => User::query()
                ->orderBy('name')
                ->get(['id', 'name', 'email']),
        ]);
    }
}
