<?php

namespace App\Http\Controllers;

use App\Models\OcrJob;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class MasterConsoleController extends Controller
{
    /**
     * Console unique Master : rôles, jobs OCR, localisation / marque, liens outils.
     */
    public function show(Request $request)
    {
        abort_unless($request->user()?->can('view any role'), 403);

        $roles = Role::query()
            ->where('name', '!=', 'master')
            ->withCount('users')
            ->withCount('permissions')
            ->orderBy('name')
            ->paginate(10, ['*'], 'roles_page')
            ->withQueryString();

        $ocrJobs = null;
        if ($request->user()->can('viewAny', OcrJob::class)) {
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

        return view('master.console', compact('roles', 'ocrJobs'));
    }
}
