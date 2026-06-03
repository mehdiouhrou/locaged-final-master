<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\DocumentMovement;
use App\Models\PhysicalLocation;
use App\Models\Category;
use App\Models\Department;
use App\Models\SubDepartment;
use App\Models\Service;
use App\Services\DocumentSearchService;
use App\Services\WorkflowApprovalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use ZipArchive;

class DocumentsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $fileType = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $room = '';
    public $author = '';
    public $tags = '';
    public $category = ''; // Category ID filter
    public $service = ''; // Service ID filter
    public $boxId = ''; // Box ID filter for physical location
    public $favoritesOnly = false;

    // Payment filters
    public $filterSupplier  = '';
    public $filterAmountMin = '';
    public $filterAmountMax = '';
    public $filterPeriod    = '';
    public $filterPieceType = '';

    // Hierarchy filter (department / sub-department / service)
    // Encoded as e.g. "department:5", "subdepartment:8", "service:12".
    public $hierarchy = '';

    // Lightweight search suggestions (used mainly on approvals page)
    public array $searchResults = [];
    public bool $showSearchDropdown = false;

    public $documentsIds = [];
    public $checkedDocuments = []; // IDs of selected documents
    public $selectAll = false;
    public $page = 1;

    // Pagination page size
    public int $perPage = 10;



    // When true (e.g., dashboard), show only items awaiting approval for the current user
    public bool $showOnlyPendingApprovals = false;

    // When true, the status filter is locked and cannot be changed
    // Used when service users access pending documents from dashboard
    public bool $lockStatusFilter = false;

    /** Motif optionnel pour refus groupé */
    public string $bulkDeclineReason = '';

    /** Batch IDs dont les lignes sont repliées (accordéon) */
    public array $collapsedBatches = [];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'fileType' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'room' => ['except' => ''],
        'author' => ['except' => ''],
        'tags' => ['except' => ''],
        'category' => ['except' => ''],
        'service' => ['except' => ''],
        'boxId' => ['except' => ''],
        'favoritesOnly'    => ['except' => false],
        'perPage'          => ['except' => 10],
        'hierarchy'        => ['except' => ''],
        'filterSupplier'   => ['except' => ''],
        'filterAmountMin'  => ['except' => ''],
        'filterAmountMax'  => ['except' => ''],
        'filterPeriod'     => ['except' => ''],
        'filterPieceType'  => ['except' => ''],
    ];

    public function mount(): void
    {
        // Check if status filter should be locked (e.g., service users from dashboard pending card)
        if (request()->get('lock_status')) {
            $this->lockStatusFilter = true;
        }
    }

    public function updated($field)
    {
        // Reset to first page when any filter changes
        if (in_array($field, ['search', 'status', 'fileType', 'dateFrom', 'dateTo', 'author', 'tags', 'category', 'service', 'boxId', 'favoritesOnly', 'perPage', 'hierarchy', 'filterSupplier', 'filterPeriod', 'filterAmountMin', 'filterAmountMax', 'filterPieceType'])) {
            $this->resetPage();
        }

        // Keep search suggestions in sync as the user types or changes filters
        if ($field === 'search' || $field === 'hierarchy' || $field === 'status' || $field === 'category') {
            $this->updateSearchSuggestions();
        }
    }



    public function resetFilters()
    {
        $this->search = '';
        $this->status = '';
        $this->fileType = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->room = '';
        $this->author = '';
        $this->tags = '';
        $this->category = '';
        $this->service = '';
        $this->boxId = '';
        $this->favoritesOnly   = false;
        $this->perPage         = 10;
        $this->hierarchy       = '';
        $this->filterSupplier  = '';
        $this->filterAmountMin = '';
        $this->filterAmountMax = '';
        $this->filterPeriod    = '';
        $this->filterPieceType = '';
        $this->searchResults   = [];
        $this->showSearchDropdown = false;

        $this->selectAll = false;
        $this->checkedDocuments = [];

        $this->resetPage();
    }


    public function updatedSelectAll($value)
    {
        // Kept for backwards compatibility if selectAll is ever bound directly.
        if ($value) {
            $this->checkedDocuments = $this->documentsIds;
        } else {
            $this->checkedDocuments = [];
        }
    }

    public function toggleSelectAll(): void
    {
        $this->selectAll = ! $this->selectAll;
        $this->checkedDocuments = $this->selectAll ? $this->documentsIds : [];
    }

    public function selectHierarchy(string $type, int $id): void
    {
        $this->hierarchy = $type . ':' . $id;
        $this->resetPage();
        $this->updateSearchSuggestions();
    }

    public function clearHierarchy(): void
    {
        $this->hierarchy = '';
        $this->resetPage();
        $this->updateSearchSuggestions();
    }

    public function openSearchResult(int $documentId): void
    {
        $doc = Document::with('latestVersion')->find($documentId);
        if (! $doc || ! $doc->latestVersion) {
            return;
        }

        $params = ['id' => $doc->latestVersion->id];
        if ($this->showOnlyPendingApprovals) {
            $params['approval'] = 1;
        }

        $this->redirectRoute('document-versions.preview', $params);
    }

    public function downloadSelected()
    {
        if (empty($this->checkedDocuments)) {
            session()->flash('error', 'No documents selected for download.');
            return null;
        }

        // Get all selected documents
        $documents = Document::whereIn('id', $this->checkedDocuments)->get();

        if ($documents->isEmpty()) {
            session()->flash('error', 'Selected documents not found.');
            return null;
        }

        // Create a temporary ZIP file
        $zipFileName = 'documents_' . now()->timestamp . '.zip';
        $zipFilePath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            foreach ($documents as $doc) {
                if (!empty($doc->latestVersion) && Storage::exists($doc->latestVersion->file_path)) {
                    $zip->addFile(
                        Storage::path($doc->latestVersion->file_path),
                        basename($doc->latestVersion->file_path)
                    );
                }
            }

            // Check if any files were added
            if ($zip->numFiles === 0) {
                $zip->close();
                session()->flash('error', 'No files found for the selected documents.');
                return back();
            }

            $zip->close();
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }


    public function render()
    {
        // Base documents query (database only; we do not depend on external search
        // here because the search cluster may be down and users expect search to
        // always work based on the database state.)
        // Optimized: removed heavy 'auditLogs.user' eager loading for performance
        // auditLogs are loaded lazily in the view only when needed
        $documentsQuery = Document::with([
            'subcategory', 'department', 'box.shelf.row.room',
            'createdBy', 'latestVersion'
        ]);

        // Ne jamais lister les documents soft-deleted dans la vue principale (corbeille / destruction ailleurs).
        if (Schema::hasColumn('documents', 'deleted_at')) {
            $documentsQuery->whereNull('documents.deleted_at');
        }

        // Hide expired documents by default (unless explicitly filtered for "expired")
        // Only show expired documents when user selects the "expired" status filter
        if ($this->status !== 'expired' && Schema::hasColumn('documents', 'is_expired')) {
            $documentsQuery->where(function ($q) {
                $q->where('is_expired', false)
                  ->orWhereNull('is_expired');
            });
        }

        // Apply hierarchy filter (department / sub-department / service)
        $this->applyHierarchyToQuery($documentsQuery);

        // Pending-approvals mode: default to pending status, but allow user filter
        if ($this->showOnlyPendingApprovals) {
            if ($this->status === '' || $this->status === null) {
                $documentsQuery->where('status', 'pending');
            }

            // For users who ONLY have "view own document" permission (no service/department/global view),
            // restrict the approvals table to their own uploads.
            $user = auth()->user();
            if ($user && ! $user->can('approve', Document::class)) {
                $canViewAny        = $user->can('view any document');
                $canViewDepartment = $user->can('view department document');
                $canViewService    = $user->can('view service document');
                $canViewOwn        = $user->can('view own document');

                if (! $canViewAny && ! $canViewDepartment && ! $canViewService && $canViewOwn) {
                    $documentsQuery->where('created_by', $user->id);
                }
            }
        }

        // Full-text search: title AND OCR content from latest version
        if (! empty($this->search)) {
            $searchTerm = '%' . strtolower($this->search) . '%';
            $documentsQuery->where(function ($q) use ($searchTerm) {
                // Search in document title
                $q->whereRaw('LOWER(title) LIKE ?', [$searchTerm])
                  // Also search in OCR text from the latest version
                  ->orWhereHas('latestVersion', function ($sub) use ($searchTerm) {
                      $sub->whereRaw('LOWER(ocr_text) LIKE ?', [$searchTerm]);
                  });
            });
        }

        // Apply remaining filters identically for both approvals and normal views
        $documents = $documentsQuery
            // Status filter (overrides defaults above when explicitly set)
            ->when($this->status && $this->status !== 'all', function($q) {
                if ($this->status === 'expired') {
                    // Show documents that are either:
                    // 1. Marked as expired (is_expired = true)
                    // 2. OR have expire_at date in the past (even if not marked yet)
                    $q->where(function ($expQ) {
                        $expQ->where(function ($dateQ) {
                            $dateQ->whereNotNull('expire_at')
                                ->whereDate('expire_at', '<=', now());
                        });
                        if (Schema::hasColumn('documents', 'is_expired')) {
                            $expQ->orWhere('is_expired', true);
                        }
                    });
                } else {
                    $q->where('status', $this->status);
                }
            })
            ->when($this->fileType, fn($q) =>
                $q->whereHas('latestVersion', function ($q) {
                    $q->where('file_type', $this->fileType);
                })
            )
            ->when($this->favoritesOnly, function ($q) {
                $q->whereHas('favoritedByUsers', function ($q2) {
                    $q2->where('user_id', auth()->id());
                });
            })
            ->when($this->category, function ($q) {
                if ($this->category === 'uncategorized') {
                    $q->whereNull('category_id');
                } else {
                    $q->where('category_id', $this->category);
                }
            })
            ->when($this->service, function ($q) {
                $q->where('service_id', $this->service);
            })
            // Author filter matches metadata->author, latest version uploader, or document owner
            ->when($this->author, function ($q) {
                $author = $this->author;
                $q->where(function ($sub) use ($author) {
                    $sub->where('metadata->author', 'like', '%' . $author . '%')
                        ->orWhereHas('latestVersion.uploadedBy', function ($q2) use ($author) {
                            $q2->where('full_name', 'like', '%' . $author . '%')
                               ->orWhere('email', 'like', '%' . $author . '%');
                        })
                        ->orWhereHas('createdBy', function ($q3) use ($author) {
                            $q3->where('full_name', 'like', '%' . $author . '%')
                               ->orWhere('email', 'like', '%' . $author . '%');
                        });
                });
            })
            ->when($this->room, function ($q) {
                $room = \App\Models\Room::where('name', $this->room)->first();
                if ($room) {
                    $boxIds = \App\Models\Box::whereHas('shelf.row.room', function($q2) use ($room) {
                        $q2->where('id', $room->id);
                    })->pluck('id');
                    $q->whereIn('box_id', $boxIds);
                }
            })
            ->when($this->boxId, function ($q) {
                $q->where('box_id', $this->boxId);
            })
            ->when($this->dateFrom, fn($q) =>
                $q->whereDate('created_at', '>=', $this->dateFrom)
            )
            ->when($this->dateTo, fn($q) =>
                $q->whereDate('created_at', '<=', $this->dateTo)
            )
            // Tags filter - filters documents that have ANY of the specified tags
            ->when($this->tags, function ($q) {
                $tagsInput = trim($this->tags);
                if (empty($tagsInput)) {
                    return;
                }
                
                // Split by comma and clean up each tag name
                $tagsList = array_map('trim', explode(',', $tagsInput));
                $tagsList = array_filter($tagsList, fn($t) => !empty($t));
                
                if (empty($tagsList)) {
                    return;
                }
                
                $q->whereHas('tags', function ($tagQuery) use ($tagsList) {
                    $tagQuery->where(function ($sub) use ($tagsList) {
                        foreach ($tagsList as $tagName) {
                            $sub->orWhereRaw('LOWER(name) LIKE ?', ['%' . strtolower($tagName) . '%']);
                        }
                    });
                });
            })
            ->when($this->filterSupplier, function ($q) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.supplier')) LIKE ?", ['%' . $this->filterSupplier . '%']);
            })
            ->when($this->filterAmountMin !== '' && $this->filterAmountMin !== null, function ($q) {
                $q->where('amount', '>=', (float) $this->filterAmountMin);
            })
            ->when($this->filterAmountMax !== '' && $this->filterAmountMax !== null, function ($q) {
                $q->where('amount', '<=', (float) $this->filterAmountMax);
            })
            ->when($this->filterPeriod, function ($q) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.period')) = ?", [$this->filterPeriod]);
            })
            ->when($this->filterPieceType, function ($q) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.piece_type')) = ?", [$this->filterPieceType]);
            })
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');
        
        // Paginate for display first (fast)
        $documents = $documents->paginate($this->perPage);
        
        // Get document IDs only from current page for navigation
        // Full navigation across all pages is too slow - limit to current page only
        $this->documentsIds = $documents->pluck('id')->toArray();

        // Lazy-load movements - only used in move modal (not needed for pagination)
        $movements = collect(); // Will be loaded via AJAX when modal opens if needed
        
        // Load rooms for move modal hierarchical selection - cached
        $rooms = cache()->remember('rooms_with_hierarchy', 300, function() {
            return \App\Models\Room::with(['rows.shelves.boxes'])->get();
        });

        // Skip search suggestions during pagination for performance
        // Only update when actually searching
        if ($this->search) {
            $this->updateSearchSuggestions(false);
        }

        // Cache hierarchy departments per user (changes rarely)
        $user = auth()->user();
        $hierarchyDepartments = collect();

        if ($user) {
            $cacheKey = 'hierarchy_departments_user_' . $user->id;
            $hierarchyDepartments = cache()->remember($cacheKey, 600, function() use ($user) {
                $user->loadMissing(['subDepartments', 'services']);

                $isMasterOrSuper = $user->can('view any role') || $user->can('view organization wide reports');
                $isDepartmentAdmin = $user->can('filter audit logs by assigned departments');
                $isDivisionChief = $user->can('view subdepartment scoped documents');

                if ($isMasterOrSuper) {
                    return Department::withoutGlobalScopes()
                        ->with(['subDepartments.services'])
                        ->orderBy('name')
                        ->get();
                } else {
                    // Departments directly from pivot (bypassing Department global scope)
                    $userDeptIdsRaw = DB::table('department_user')
                        ->where('user_id', $user->id)
                        ->pluck('department_id');

                    $departments = Department::withoutGlobalScopes()
                        ->whereIn('id', $userDeptIdsRaw)
                        ->orderBy('name')
                        ->get();

                    // Determine visible sub-departments
                    if ($isDepartmentAdmin) {
                        $deptIds = $departments->pluck('id');
                        $subDepartments = SubDepartment::whereIn('department_id', $deptIds)->get();
                    } else {
                        $subDepartments = $user->subDepartments; // via pivot
                    }

                    // Determine visible services
                    if ($isDivisionChief || $isDepartmentAdmin) {
                        $subIds = $subDepartments->pluck('id');
                        $services = Service::whereIn('sub_department_id', $subIds)->get();
                    } else {
                        $services = $user->services; // via pivot
                    }

                    // Assemble hierarchy tree limited to these units
                    $subByDept = $subDepartments->groupBy('department_id');
                    $servicesBySub = $services->groupBy('sub_department_id');

                    return $departments->map(function ($dept) use ($subByDept, $servicesBySub) {
                        $dept->visibleSubDepartments = ($subByDept[$dept->id] ?? collect())
                            ->map(function ($sub) use ($servicesBySub) {
                                $sub->visibleServices = $servicesBySub[$sub->id] ?? collect();
                                return $sub;
                            });
                        return $dept;
                    });
                }
            });
        }

        return view('livewire.documents-table', [
            'documents' => $documents,
            'folders'   => collect(), // Empty collection - folders feature removed
            'movements' => $movements,
            'rooms'     => $rooms,
            'hierarchyDepartments' => $hierarchyDepartments,
            'documentsIds' => $this->documentsIds,
            'filterCategories' => Category::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function updateSearchSuggestions(bool $resetWhenEmpty = true): void
    {
        // Currently we only show suggestions on the approvals page.
        if (! $this->showOnlyPendingApprovals) {
            $this->searchResults = [];
            $this->showSearchDropdown = false;
            return;
        }

        $term = trim((string) $this->search);
        // Require at least 1 visible character; show suggestions as soon as
        // the user starts typing anything non-empty.
        if ($term === '') {
            if ($resetWhenEmpty) {
                $this->searchResults = [];
                $this->showSearchDropdown = false;
            }
            return;
        }

        $query = Document::with('latestVersion');

        if (Schema::hasColumn('documents', 'deleted_at')) {
            $query->whereNull('documents.deleted_at');
        }

        // Only documents that actually have a version
        $query->whereHas('latestVersion');

        // Apply same hierarchy restrictions as main approvals table
        $this->applyHierarchyToQuery($query);

        // Default pending status unless user changed the filter
        if ($this->status === '' || $this->status === null) {
            $query->where('status', 'pending');
        } elseif ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        // Respect "own documents only" scenario
        $user = auth()->user();
        if ($user && ! $user->can('approve', Document::class)) {
            $canViewAny        = $user->can('view any document');
            $canViewDepartment = $user->can('view department document');
            $canViewService    = $user->can('view service document');
            $canViewOwn        = $user->can('view own document');

            if (! $canViewAny && ! $canViewDepartment && ! $canViewService && $canViewOwn) {
                $query->where('created_by', $user->id);
            }
        }

        if ($this->category === 'uncategorized') {
            $query->whereNull('category_id');
        } elseif ($this->category !== '' && $this->category !== null) {
            $query->where('category_id', $this->category);
        }

        // Full-text search: title AND OCR content
        $searchLike = '%' . strtolower($term) . '%';
        $query->where(function ($q) use ($searchLike) {
            $q->whereRaw('LOWER(title) LIKE ?', [$searchLike])
              ->orWhereHas('latestVersion', function ($sub) use ($searchLike) {
                  $sub->whereRaw('LOWER(ocr_text) LIKE ?', [$searchLike]);
              });
        });

        $docs = $query->orderByDesc('created_at')->limit(10)->get();

        $this->searchResults = $docs->filter(fn($d) => $d->latestVersion)
            ->map(function ($d) {
                return [
                    'id' => $d->id,
                    'title' => $d->title,
                    'status' => $d->status,
                ];
            })->values()->all();

        $this->showSearchDropdown = ! empty($this->searchResults);
    }

    private function applyHierarchyToFilters(array &$filters): void
    {
        if (! $this->hierarchy) {
            return;
        }

        [$type, $id] = explode(':', $this->hierarchy) + [null, null];
        $id = (int) $id;
        if (! $id) {
            return;
        }

        if ($type === 'department') {
            $filters['department_id'] = $id;
        } elseif ($type === 'subdepartment') {
            $serviceIds = Service::where('sub_department_id', $id)->pluck('id')->all();
            if (! empty($serviceIds)) {
                $filters['service_ids'] = $serviceIds;
            }
        } elseif ($type === 'service') {
            $filters['service_ids'] = [$id];
        }
    }

    private function applyHierarchyToQuery($query): void
    {
        if (! $this->hierarchy) {
            return;
        }

        [$type, $id] = explode(':', $this->hierarchy) + [null, null];
        $id = (int) $id;
        if (! $id) {
            return;
        }

        if ($type === 'department') {
            $query->where('department_id', $id);
        } elseif ($type === 'subdepartment') {
            $serviceIds = Service::where('sub_department_id', $id)->pluck('id');
            if ($serviceIds->isNotEmpty()) {
                $query->whereIn('service_id', $serviceIds);
            }
        } elseif ($type === 'service') {
            $query->where('service_id', $id);
        }
    }

    public function toggleFavorite(int $documentId): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }
        $isFav = $user->favoriteDocuments()->where('document_id', $documentId)->exists();
        if ($isFav) {
            $user->favoriteDocuments()->detach($documentId);
        } else {
            $user->favoriteDocuments()->attach($documentId);
        }
        $this->dispatch('document-updated');
        $this->resetPage();
    }

    public function bulkApprove(): void
    {
        if (empty($this->checkedDocuments)) {
            session()->flash('error', 'No documents selected for approval.');
            return;
        }

        Gate::authorize('approve', Document::class);

        $docs = Document::whereIn('id', $this->checkedDocuments)->get();
        $approvedCount = 0;

        foreach ($docs as $doc) {
            if ($doc->status !== DocumentStatus::Pending->value) {
                continue;
            }

            $doc->status = DocumentStatus::Approved->value;
            $doc->save();
            $doc->logAction('approved');

            // Queue OCR only once the document is approved.
            $doc->queueOcrIfNeeded();

            $approvedCount++;
        }

        $this->checkedDocuments = [];
        $this->selectAll = false;
        $this->dispatch('document-updated');
        $this->resetPage();

        if ($approvedCount > 0) {
            session()->flash('success', $approvedCount . ' document(s) approved.');
        } else {
            session()->flash('error', 'No pending documents in the selected items.');
        }
    }

    /**
     * Replier / déplier les lignes d'un batch dans la vue approbations (accordéon).
     */
    public function toggleBatch(string $batchId): void
    {
        if (in_array($batchId, $this->collapsedBatches, true)) {
            $this->collapsedBatches = array_values(
                array_filter($this->collapsedBatches, fn ($id) => $id !== $batchId)
            );
        } else {
            $this->collapsedBatches[] = $batchId;
        }
    }

    /**
     * Approuver tous les documents d'un même batch (dossier uploadé ensemble).
     */
    public function approveBatch(string $batchId, ?string $comments = null): void
    {
        Gate::authorize('approve', Document::class);

        $user    = auth()->user();
        $service = app(WorkflowApprovalService::class);

        $docs = Document::where('batch_id', $batchId)
            ->where('status', 'pending')
            ->get();

        $approved = 0;
        $errors   = 0;

        foreach ($docs as $doc) {
            try {
                // Initialiser le workflow si pas encore fait
                $hasApprovals = DocumentApproval::where('document_id', $doc->id)->exists();
                if (! $hasApprovals) {
                    $service->initializeWorkflow($doc);
                    $doc->refresh();
                }

                // Vérifier si ce document est encore pending après l'init
                if ($doc->fresh()->status !== 'pending') {
                    $approved++; // approuvé directement par initializeWorkflow (pas de règle)
                    continue;
                }

                $level = $service->getCurrentLevel($doc);
                if (! $level) {
                    continue;
                }

                $result = $service->approveLevel($doc, $level, $user, $comments);
                if ($result) {
                    $approved++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                report($e);
                $errors++;
            }
        }

        $this->checkedDocuments = [];
        $this->selectAll = false;
        $this->dispatch('document-updated');
        $this->resetPage();

        if ($approved > 0) {
            session()->flash('success', "$approved document(s) du dossier approuvé(s) avec succès.");
        }
        if ($errors > 0) {
            session()->flash('error', "$errors document(s) n'ont pas pu être approuvés.");
        }
        if ($approved === 0 && $errors === 0) {
            session()->flash('error', 'Aucun document en attente pour ce dossier.');
        }
    }

    /**
     * Refuser tous les documents d'un même batch (dossier uploadé ensemble).
     */
    public function declineBatch(string $batchId, string $reason = ''): void
    {
        Gate::authorize('decline', Document::class);

        $user    = auth()->user();
        $service = app(WorkflowApprovalService::class);
        $reason  = trim($reason ?: $this->bulkDeclineReason) ?: 'Refusé en lot';

        $docs = Document::where('batch_id', $batchId)
            ->where('status', 'pending')
            ->get();

        $declined = 0;
        $errors   = 0;

        foreach ($docs as $doc) {
            try {
                // Initialiser le workflow si pas encore fait
                $hasApprovals = DocumentApproval::where('document_id', $doc->id)->exists();
                if (! $hasApprovals) {
                    $service->initializeWorkflow($doc);
                    $doc->refresh();
                }

                // Vérifier si ce document est encore pending après l'init
                if ($doc->fresh()->status !== 'pending') {
                    continue;
                }

                $level = $service->getCurrentLevel($doc);
                if (! $level) {
                    continue;
                }

                $result = $service->declineLevel($doc, $level, $user, $reason);
                if ($result) {
                    $declined++;
                } else {
                    $errors++;
                }
            } catch (\Throwable $e) {
                report($e);
                $errors++;
            }
        }

        $this->checkedDocuments = [];
        $this->selectAll = false;
        $this->bulkDeclineReason = '';
        $this->dispatch('document-updated');
        $this->resetPage();

        if ($declined > 0) {
            session()->flash('success', "$declined document(s) du dossier refusé(s).");
        }
        if ($errors > 0) {
            session()->flash('error', "$errors document(s) n'ont pas pu être refusés.");
        }
        if ($declined === 0 && $errors === 0) {
            session()->flash('error', 'Aucun document en attente pour ce dossier.');
        }
    }

    public function bulkDecline(): void
    {
        if (empty($this->checkedDocuments)) {
            session()->flash('error', 'No documents selected for rejection.');
            return;
        }

        Gate::authorize('decline', Document::class);

        $this->validate([
            'bulkDeclineReason' => ['nullable', 'string', 'max:5000'],
        ]);

        $docs = Document::whereIn('id', $this->checkedDocuments)->get();
        $declinedCount = 0;

        foreach ($docs as $doc) {
            if ($doc->status !== DocumentStatus::Pending->value) {
                continue;
            }

            $doc->status = DocumentStatus::Declined->value;
            $meta = is_array($doc->metadata) ? $doc->metadata : (array) ($doc->metadata ?? []);
            $declineReason = trim($this->bulkDeclineReason);
            if ($declineReason !== '') {
                $meta['decline_reason'] = $declineReason;
            } else {
                unset($meta['decline_reason']);
            }
            $doc->metadata = $meta;
            $doc->save();
            $doc->logAction('declined', null, ['decline_reason' => $declineReason !== '' ? $declineReason : null]);
            $declinedCount++;
        }

        $this->checkedDocuments = [];
        $this->selectAll = false;
        $this->bulkDeclineReason = '';
        $this->dispatch('document-updated');
        $this->resetPage();

        if ($declinedCount > 0) {
            session()->flash('success', $declinedCount . ' document(s) rejected.');
        } else {
            session()->flash('error', 'No pending documents in the selected items.');
        }
    }
}
