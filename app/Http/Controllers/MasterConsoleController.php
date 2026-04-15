<?php

namespace App\Http\Controllers;

use App\Models\OcrJob;
use App\Models\User;
use App\Support\Branding;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MasterConsoleController extends Controller
{
    /**
     * Console unique Master : rôles, jobs OCR, localisation / marque, liens outils.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        abort_unless($user && ($user->can('view any role') || $user->hasRole('master')), 403);

        $roles = Role::query()
            ->where('name', '!=', 'master')
            ->withCount('users')
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(10, ['*'], 'roles_page')
            ->withQueryString();

        $ocrJobs = null;
        $ocrOverview = null;
        if ($request->user()->can('viewAny', OcrJob::class)) {
            $ocrOverview = [
                'active' => OcrJob::query()->whereIn('status', [OcrJob::STATUS_QUEUED, OcrJob::STATUS_PROCESSING])->count(),
                'failed' => OcrJob::query()->where('status', OcrJob::STATUS_FAILED)->count(),
            ];
            $ocrJobs = OcrJob::query()
                ->with([
                    'documentVersion' => function ($query) {
                        $query->withTrashed()->with([
                            'document' => function ($query) {
                                $query->withTrashed();
                            },
                        ]);
                    },
                ])
                ->latest()
                ->paginate(10, ['*'], 'ocr_page')
                ->withQueryString();
        }

        $userCount = User::count();
        $maxUsers = Branding::getMaxUsers();

        return view('master.console', [
            'roles' => $roles,
            'ocrJobs' => $ocrJobs,
            'ocrOverview' => $ocrOverview,
            'userCount' => $userCount,
            'maxUsers' => $maxUsers,
            'orgRootName' => Branding::getOrgRootName(),
            'appTimezone' => Branding::getTimezone(),
        ]);
    }
}
