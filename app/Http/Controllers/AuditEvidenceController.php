<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditEvidenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditEvidenceController extends Controller
{
    public function export(Request $request, AuditEvidenceService $service)
    {
        Gate::authorize('viewAny', User::class);
        abort_unless(
            auth()->user()?->can('view audit log')
            || auth()->user()?->can('view system activity log'),
            403
        );

        $filters = [
            'log_type' => $request->query('logType', $request->query('log_type', 'all')),
            'date_from' => $request->query('dateFrom', $request->query('date_from')),
            'date_to' => $request->query('dateTo', $request->query('date_to')),
            'user_id' => $request->query('userId', $request->query('user_id')),
            'department_id' => $request->query('departmentId', $request->query('department_id')),
            'action_type' => $request->query('actionType', $request->query('action_type')),
            'search' => $request->query('search'),
        ];

        $result = $service->buildSignedEvidencePackage(auth()->user(), array_filter($filters, fn ($value) => $value !== null && $value !== ''));

        return response()->download(
            $result['local_absolute_path'],
            $result['download_name'],
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }
}
