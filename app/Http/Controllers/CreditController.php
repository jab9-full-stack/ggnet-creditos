<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\AuditLog;
use App\Models\Credit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreditController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('credits.view'), 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $agencyId = trim((string) $request->query('agency_id', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $credits = Credit::query()
            ->with([
                'client:id,code,first_name,middle_name,last_name,second_last_name,married_name,dpi,phone',
                'agency:id,code,name',
                'creditRequest:id,code,status',
            ])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery
                        ->where('code', 'like', "%{$search}%")
                        ->orWhereHas('creditRequest', function ($requestQuery) use ($search): void {
                            $requestQuery->where('code', 'like', "%{$search}%");
                        })
                        ->orWhereHas('client', function ($clientQuery) use ($search): void {
                            $clientQuery
                                ->where('code', 'like', "%{$search}%")
                                ->orWhere('first_name', 'like', "%{$search}%")
                                ->orWhere('middle_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('second_last_name', 'like', "%{$search}%")
                                ->orWhere('married_name', 'like', "%{$search}%")
                                ->orWhere('dpi', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($agencyId !== '', fn ($query) => $query->where('agency_id', $agencyId))
            ->when($dateFrom !== '', fn ($query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('credits.index', [
            'credits' => $credits,
            'search' => $search,
            'status' => $status,
            'agencyId' => $agencyId,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'statuses' => Credit::STATUSES,
            'agencies' => Agency::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'code', 'name']),
        ]);
    }

    public function show(Request $request, Credit $credit): View
    {
        abort_unless($request->user()?->can('credits.view'), 403);

        $credit->load([
            'agency:id,code,name',
            'client:id,agency_id,code,first_name,middle_name,last_name,second_last_name,married_name,dpi,phone,address_line',
            'creditRequest:id,code,status,requested_amount,requested_term_weeks,approved_at',
            'createdBy:id,name,email',
            'approvedBy:id,name,email',
            'disbursedBy:id,name,email',
            'installments.payment:id,code,amount,paid_at,method',
            'payments.receivedBy:id,name,email',
        ]);

        $auditLogs = AuditLog::query()
            ->with('user:id,name,email')
            ->where('auditable_type', Credit::class)
            ->where('auditable_id', $credit->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('credits.show', [
            'credit' => $credit,
            'auditLogs' => $auditLogs,
        ]);
    }
}
