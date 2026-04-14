<?php

namespace App\Http\Controllers\Api;

use App\Enums\DocumentStatus;
use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Service;
use App\Models\Subcategory;
use App\Services\DocumentSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class DocumentApiController extends Controller
{
    /**
     * CDC §4.1 — POST /api/documents (versement minimal : fichier + métadonnées).
     */
    public function store(Request $request): JsonResponse
    {
        Gate::authorize('create', Document::class);

        $maxFileSizeKb = (int) config('uploads.max_file_size_kb', 50000);
        $allowedExtensions = config('uploads.allowed_extensions', []);
        $mimesPart = ! empty($allowedExtensions)
            ? '|mimes:'.implode(',', $allowedExtensions)
            : '';

        $validated = $request->validate([
            'title' => 'required|string|max:500',
            'subcategory_id' => 'required|exists:subcategories,id',
            'file' => 'required|file|max:'.$maxFileSizeKb.$mimesPart,
        ]);

        $user = $request->user();

        $document = DB::transaction(function () use ($validated, $request, $user) {
            $sub = Subcategory::query()->with('category')->findOrFail($validated['subcategory_id']);
            $category = $sub->category;

            $departmentId = $user->departments()->first()?->id
                ?? DB::table('department_user')->where('user_id', $user->id)->value('department_id');
            $serviceId = $user->service_id;
            if (! $serviceId && method_exists($user, 'services')) {
                $serviceId = $user->services()->orderBy('services.id')->value('services.id');
            }

            $doc = Document::create([
                'uid' => (string) Str::uuid(),
                'title' => $validated['title'],
                'subcategory_id' => $sub->id,
                'category_id' => $category->id,
                'department_id' => $departmentId,
                'service_id' => $serviceId,
                'status' => DocumentStatus::Pending->value,
                'created_by' => $user->id,
            ]);

            $path = $request->file('file')->store('document_versions');
            $extension = $request->file('file')->getClientOriginalExtension();

            DocumentVersion::create([
                'document_id' => $doc->id,
                'file_path' => $path,
                'file_type' => getFileCategory($extension),
                'uploaded_by' => $user->id,
                'uploaded_at' => now(),
            ]);

            return $doc;
        });

        return response()->json([
            'id' => $document->id,
            'uid' => $document->uid,
            'title' => $document->title,
            'status' => $document->status,
        ], 201);
    }

    /**
     * CDC §4.1 — GET /api/documents/{id}
     */
    public function show(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        $document->loadMissing('latestVersion', 'subcategory', 'category');

        return response()->json([
            'id' => $document->id,
            'uid' => $document->uid,
            'title' => $document->title,
            'status' => $document->status,
            'category_id' => $document->category_id,
            'subcategory_id' => $document->subcategory_id,
            'file_hash' => $document->file_hash,
            'latest_version' => $document->latestVersion ? [
                'id' => $document->latestVersion->id,
                'version_number' => $document->latestVersion->version_number,
                'file_type' => $document->latestVersion->file_type,
            ] : null,
        ]);
    }

    /**
     * CDC §4.1 — GET /api/documents/search
     */
    public function search(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Document::class);

        $query = $request->string('q', '')->toString();
        $page = max(1, (int) $request->input('page', 1));
        $perPage = min(50, max(1, (int) $request->input('per_page', 15)));

        $paginator = DocumentSearchService::searchDocuments($query, [], $perPage, $page);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * CDC §4.1 — GET /api/documents/{id}/metadata
     */
    public function metadata(Request $request, Document $document): JsonResponse
    {
        Gate::authorize('view', $document);

        return response()->json([
            'id' => $document->id,
            'uid' => $document->uid,
            'title' => $document->title,
            'status' => $document->status,
            'metadata' => $document->metadata,
            'expire_at' => $document->expire_at?->toDateString(),
            'file_hash' => $document->file_hash,
        ]);
    }
}
