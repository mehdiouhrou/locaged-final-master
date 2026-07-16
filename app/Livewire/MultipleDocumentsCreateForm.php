<?php

namespace App\Livewire;

use App\Models\Box;
use App\Models\Category;
use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\Folder;
use App\Models\Room;
use App\Models\Row;
use App\Models\Service;
use App\Models\Shelf;
use App\Models\Subcategory;
use App\Models\SubDepartment;
use App\Models\Tag;
use App\Services\ClamAvScanner;
use App\Services\PdfConversionService;
use App\Services\ProfileCategoryAccessService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class MultipleDocumentsCreateForm extends Component
{
    use WithFileUploads;

    public function mount(?int $folderId = null, ?int $categoryId = null): void
    {
        $this->folderId = $folderId;
        $this->categoryId = $categoryId;
    }

    // If set, new documents will be created inside this existing folder
    public ?int $folderId = null;

    // Optional: pre-selected category (e.g. when coming from a category documents page)
    public ?int $categoryId = null;

    // Optional: create a new logical folder and put all uploaded docs inside it
    public ?string $newFolderName = null;

    public $step = 1;

    public $documents = [];

    public $newDocuments = [];

    // Controls whether one metadata form is applied to all files (true)
    // or each file has its own metadata (false).
    public bool $useSharedMetadata = true;

    // For folder uploads: relative OS paths (index-aligned with $documents)
    public array $relativePaths = [];

    /** Original client file names (index-aligned with $documents); survives Livewire better than reading uploads in Blade only */
    public array $documentOriginalNames = [];

    public $documentInfos = [];

    public $currentDocumentIndex = 0;

    // SOLUTION: Add a property to hold the data for the current form
    public $currentInfo = [];

    // Hierarchical location selection
    public $selectedRoomId = null;

    public $selectedRowId = null;

    public $selectedShelfId = null;

    public $selectedBoxId = null;

    public $selectedBoxFolderId = null;

    public $uploadProgress = 0;

    public $previewUrl;

    public $currentPreviewUrl = null;

    public $currentPreviewType = null; // image|pdf|other

    public $previewMime;

    public $previewName;

    // Prevent duplicate submissions
    public $isSubmitting = false;

    /** Saisie libre pour les étiquettes (Entrée ou virgule valide le jeton). */
    public string $tagDraft = '';

    // Duplicate detection state (DEPRECATED - now using batch detection)
    public $showDuplicateModal = false;

    public $currentDuplicates = [];

    public $duplicateDecisions = []; // index => 'upload' | 'skip'

    // Batch duplicate detection state
    public $allDuplicates = []; // ['fileIndex' => [duplicates]]

    public $filesWithDuplicates = []; // [fileIndex, fileIndex, ...]

    protected $listeners = ['previewFile'];

    /**
     * Après sélection des fichiers : scan ClamAV optionnel (voir config/clamav.php).
     */
    public function updatedDocuments(): void
    {
        if (! config('clamav.enabled') || empty($this->documents)) {
            return;
        }

        try {
            $scanner = app(ClamAvScanner::class);
            foreach ($this->documents as $file) {
                if (! is_object($file) || ! method_exists($file, 'getRealPath')) {
                    continue;
                }
                $path = $file->getRealPath();
                if (is_string($path) && $path !== '') {
                    $scanner->assertClean($path);
                }
            }
        } catch (\Throwable $e) {
            $this->documents = [];
            $this->documentOriginalNames = [];
            $this->addError('documents', $e->getMessage());
            Log::warning('Upload rejected by ClamAV gate', ['message' => $e->getMessage()]);
        }
    }

    protected function rules()
    {
        $maxFileSizeKb = (int) config('uploads.max_file_size_kb', 50000);
        $maxBatchFiles = (int) config('uploads.max_batch_files', 50);
        $maxBatchSizeKb = (int) config('uploads.max_batch_size_kb', 500000);
        $allowedExtensions = config('uploads.allowed_extensions', []);

        $mimesRule = ! empty($allowedExtensions)
            ? 'mimes:'.implode(',', $allowedExtensions)
            : '';

        return [
            'documents' => [
                'required',
                'array',
                'min:1',
                'max:'.$maxBatchFiles,
                function ($attribute, $value, $fail) use ($maxBatchSizeKb) {
                    if (! is_array($value) || $maxBatchSizeKb <= 0) {
                        return;
                    }

                    $totalBytes = 0;
                    foreach ($value as $file) {
                        if (method_exists($file, 'getSize')) {
                            $totalBytes += (int) $file->getSize();
                        }
                    }

                    $maxBytes = $maxBatchSizeKb * 1024;
                    if ($totalBytes > $maxBytes) {
                        $fail(__('The total size of all files exceeds the maximum of :max_mb MB.', [
                            'max_mb' => (int) floor($maxBatchSizeKb / 1024),
                        ]));
                    }
                },
            ],
            'documents.*' => trim(sprintf(
                'required|file|max:%d%s',
                $maxFileSizeKb,
                $mimesRule ? '|'.$mimesRule : ''
            ), '|'),
        ];
    }

    public function updated($name, $value)
    {
        // Reset dependent dropdowns when parent changes
        if ($name === 'selectedRoomId') {
            $this->selectedRowId = null;
            $this->selectedShelfId = null;
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;
            $this->currentInfo['box_id'] = null;
            $this->currentInfo['box_folder_id'] = null;
        } elseif ($name === 'selectedRowId') {
            $this->selectedShelfId = null;
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;
            $this->currentInfo['box_id'] = null;
            $this->currentInfo['box_folder_id'] = null;
        } elseif ($name === 'selectedShelfId') {
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;
            $this->currentInfo['box_id'] = null;
            $this->currentInfo['box_folder_id'] = null;
        } elseif ($name === 'selectedBoxId') {
            $this->currentInfo['box_id'] = $value;
            $this->selectedBoxFolderId = null;
            $this->currentInfo['box_folder_id'] = null;
        } elseif ($name === 'selectedBoxFolderId') {
            $this->currentInfo['box_folder_id'] = $value;
        }
    }

    // Methods to get child options for cascading dropdowns
    // Filter to only show rooms that contain accessible boxes
    public function getRoomsProperty()
    {
        $user = auth()->user();

        // Find rooms that have at least one accessible box
        return Room::whereHas('rows.shelves.boxes', function ($query) use ($user) {
            Box::applyPhysicalAccessFilter($query, $user, $this->currentInfo['service_id'] ?? null);
        })
            ->orderBy('name')
            ->get();
    }

    public function getRowsProperty()
    {
        if (! $this->selectedRoomId) {
            return collect();
        }

        $user = auth()->user();

        // Find rows (in selected room) that have at least one accessible box
        return Row::where('room_id', $this->selectedRoomId)
            ->whereHas('shelves.boxes', function ($query) use ($user) {
                Box::applyPhysicalAccessFilter($query, $user, $this->currentInfo['service_id'] ?? null);
            })
            ->orderBy('name')
            ->get();
    }

    public function getShelvesProperty()
    {
        if (! $this->selectedRowId) {
            return collect();
        }

        $user = auth()->user();

        // Find shelves (in selected row) that have at least one accessible box
        return Shelf::where('row_id', $this->selectedRowId)
            ->whereHas('boxes', function ($query) use ($user) {
                Box::applyPhysicalAccessFilter($query, $user, $this->currentInfo['service_id'] ?? null);
            })
            ->orderBy('name')
            ->get();
    }

    public function getBoxesProperty()
    {
        if (! $this->selectedShelfId) {
            return collect();
        }

        $query = Box::where('shelf_id', $this->selectedShelfId)
            ->forUser(auth()->user());

        if (! empty($this->currentInfo['service_id'])) {
            $query->where('service_id', $this->currentInfo['service_id']);
        }

        return $query->get();
    }

    public function getBoxFoldersProperty()
    {
        if (! $this->selectedBoxId) {
            return collect();
        }

        return \App\Models\BoxFolder::where('box_id', $this->selectedBoxId)
            ->orderBy('name')
            ->get();
    }

    // Helper to load the current document's info into the form property
    private function loadCurrentInfo()
    {

        if (isset($this->documentInfos[$this->currentDocumentIndex])) {
            $this->currentInfo = $this->documentInfos[$this->currentDocumentIndex];
        }

        // Pre-selected category from route/context only sets category_id (no org on categories).
        if ($this->categoryId && empty($this->currentInfo['category_id'])) {
            $category = Category::find($this->categoryId);
            if ($category) {
                $this->currentInfo['category_id'] = $category->id;
            }
        }

        // Prefill author / email from authenticated user
        $user = auth()->user();
        if ($user) {
            $this->currentInfo['author'] = $user->full_name ?: ($user->name ?? '');
            $this->currentInfo['email'] = $user->email ?? '';
        }

        // Department / sub-department / service always follow the account (pivots), not the category.
        $this->hydrateOrgFromUserContext();

        if (! array_key_exists('digital_only', $this->currentInfo)) {
            $this->currentInfo['digital_only'] = false;
        }

        // Ensure a server-side default color for every document to avoid client-only defaults
        if (empty($this->currentInfo['color'])) {
            $this->currentInfo['color'] = 'Blue';
        }

        if (empty($this->currentInfo['created_at'])) {
            $this->currentInfo['created_at'] = now()->format('Y-m-d\TH:i');
        }

        // Clear any stale duplicate decisions when loading file info
        // This ensures fresh duplicate checks when user navigates between files
        unset($this->duplicateDecisions[$this->currentDocumentIndex]);

        $this->syncPhysicalLocationSelectorsFromCurrentInfo();
        $this->normalizeCurrentInfoTags();
        $this->updateCurrentPreview();
        // Ensure expiry is auto-calculated on first load when subcategory is present
        $this->recalculateExpiry();
    }

    /**
     * Migre l’ancien champ tags (ids) vers tag_ids / new_tag_names et garantit des tableaux valides.
     */
    private function normalizeCurrentInfoTags(): void
    {
        if (! isset($this->currentInfo['tag_ids']) || ! is_array($this->currentInfo['tag_ids'])) {
            $this->currentInfo['tag_ids'] = [];
        }
        if (! isset($this->currentInfo['new_tag_names']) || ! is_array($this->currentInfo['new_tag_names'])) {
            $this->currentInfo['new_tag_names'] = [];
        }

        if (isset($this->currentInfo['tags']) && is_array($this->currentInfo['tags'])) {
            foreach ($this->currentInfo['tags'] as $id) {
                if ($id === null || $id === '') {
                    continue;
                }
                $this->currentInfo['tag_ids'][] = (int) $id;
            }
            unset($this->currentInfo['tags']);
        }

        $this->currentInfo['tag_ids'] = array_values(array_unique(array_filter(
            array_map('intval', $this->currentInfo['tag_ids']),
            fn (int $id) => $id > 0
        )));

        $seenLower = [];
        $names = [];
        foreach ($this->currentInfo['new_tag_names'] as $n) {
            $t = trim((string) $n);
            if ($t === '') {
                continue;
            }
            $k = mb_strtolower($t);
            if (isset($seenLower[$k])) {
                continue;
            }
            $seenLower[$k] = true;
            $names[] = $t;
        }
        $this->currentInfo['new_tag_names'] = $names;
    }

    public function addTagsFromDraft(): void
    {
        $raw = trim($this->tagDraft);
        if ($raw === '') {
            return;
        }

        $this->normalizeCurrentInfoTags();

        $parts = preg_split('/[,;]+/u', $raw) ?: [];
        if (count($parts) <= 1) {
            $parts = [$raw];
        }

        foreach ($parts as $part) {
            $token = trim((string) $part);
            if ($token === '') {
                continue;
            }
            $this->addOneTagToken($token);
        }

        $this->tagDraft = '';
    }

    private function addOneTagToken(string $token): void
    {
        if (ctype_digit($token)) {
            $id = (int) $token;
            if (Tag::query()->whereKey($id)->exists()) {
                if (! in_array($id, $this->currentInfo['tag_ids'], true)) {
                    $this->currentInfo['tag_ids'][] = $id;
                }

                return;
            }
        }

        $existing = Tag::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($token)])
            ->first();

        if ($existing) {
            if (! in_array($existing->id, $this->currentInfo['tag_ids'], true)) {
                $this->currentInfo['tag_ids'][] = $existing->id;
            }

            return;
        }

        $lower = mb_strtolower($token);
        foreach ($this->currentInfo['new_tag_names'] as $n) {
            if (mb_strtolower($n) === $lower) {
                return;
            }
        }

        $this->currentInfo['new_tag_names'][] = $token;
    }

    public function removeTagId(int $id): void
    {
        $this->normalizeCurrentInfoTags();
        $this->currentInfo['tag_ids'] = array_values(array_filter(
            $this->currentInfo['tag_ids'],
            fn (int $tid) => $tid !== $id
        ));
    }

    public function removeNewTagAtIndex(int $index): void
    {
        $this->normalizeCurrentInfoTags();
        unset($this->currentInfo['new_tag_names'][$index]);
        $this->currentInfo['new_tag_names'] = array_values($this->currentInfo['new_tag_names']);
    }

    /**
     * @return list<int>
     */
    private function resolveTagIdsForDocument(array $metadata): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $metadata['tag_ids'] ?? []),
            fn (int $id) => $id > 0
        )));

        foreach ($metadata['new_tag_names'] ?? [] as $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $tag = Tag::query()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->first();

            if (! $tag) {
                $tag = Tag::create(['name' => $name]);
            }

            if (! in_array($tag->id, $ids, true)) {
                $ids[] = $tag->id;
            }
        }

        return $ids;
    }

    // Check for duplicates for the current file
    private function checkCurrentDuplicates(): array
    {
        $meta = $this->currentInfo;

        // PERFORMANCE OPTIMIZATION: Early return if required fields are missing
        if (empty($meta['title']) || empty($meta['created_at']) || empty($meta['department_id'])) {
            Log::info('Duplicate check skipped - missing required fields', [
                'has_title' => ! empty($meta['title']),
                'has_created_at' => ! empty($meta['created_at']),
                'has_department_id' => ! empty($meta['department_id']),
            ]);

            return [];
        }

        Log::info('Checking for duplicates', [
            'title' => $meta['title'],
            'department_id' => $meta['department_id'],
            'created_at' => $meta['created_at'],
            'created_at_date_only' => \Carbon\Carbon::parse($meta['created_at'])->format('Y-m-d'),
        ]);

        // Extract just the date portion for comparison
        $searchDate = \Carbon\Carbon::parse($meta['created_at'])->format('Y-m-d');

        Log::info('Searching database for duplicates with date', ['search_date' => $searchDate]);

        // PERFORMANCE OPTIMIZATION: Use exists() for faster check before fetching records
        $hasMatches = Document::whereRaw('LOWER(title) = ?', [strtolower($meta['title'])])
            ->where('department_id', $meta['department_id'])
            ->whereDate('created_at', $searchDate)
            ->exists();

        if (! $hasMatches) {
            Log::info('No duplicates found');

            return []; // No duplicates found, skip expensive mapping
        }

        Log::info('Duplicates found, fetching details');

        return Document::whereRaw('LOWER(title) = ?', [strtolower($meta['title'])])
            ->where('department_id', $meta['department_id'])
            ->whereDate('created_at', $searchDate)
            ->limit(10) // Limit to 10 duplicates max for performance
            ->get(['id', 'title'])
            ->map(fn ($d) => [
                'id' => $d->id,
                'title' => $d->title,
                'url' => route('documents.show', ['document' => $d->id]),
            ])
            ->toArray();
    }

    // Check and show modal if needed when navigating
    private function checkAndShowDuplicateModal(): void
    {
        // Always check for duplicates, even if user previously made a decision
        // This allows them to reconsider if they navigate back

        Log::info('checkAndShowDuplicateModal called', [
            'currentInfo' => $this->currentInfo,
            'currentDocumentIndex' => $this->currentDocumentIndex,
        ]);

        $dups = $this->checkCurrentDuplicates();

        Log::info('Duplicate check result', [
            'duplicates_found' => count($dups),
            'duplicates' => $dups,
        ]);

        if (! empty($dups)) {
            $this->currentDuplicates = $dups;
            $this->showDuplicateModal = true;
            Log::info('Setting showDuplicateModal to true');
        } else {
            $this->showDuplicateModal = false;
            $this->currentDuplicates = [];
            Log::info('Setting showDuplicateModal to false (no duplicates)');
        }
    }

    // NEW: Check ALL files for duplicates at once (batch operation)
    private function checkAllFilesForDuplicates(): void
    {
        Log::info('Starting batch duplicate check for all files', [
            'total_files' => count($this->documentInfos),
        ]);

        $this->allDuplicates = [];
        $this->filesWithDuplicates = [];

        foreach ($this->documentInfos as $index => $meta) {
            Log::info("Checking file index {$index}", [
                'has_title' => ! empty($meta['title']),
                'has_created_at' => ! empty($meta['created_at']),
                'has_department_id' => ! empty($meta['department_id']),
                'title' => $meta['title'] ?? 'MISSING',
                'department_id' => $meta['department_id'] ?? 'MISSING',
            ]);

            // Skip if required fields are missing
            if (empty($meta['title']) || empty($meta['created_at']) || empty($meta['department_id'])) {
                Log::warning("Skipping file index {$index} - missing required fields");

                continue;
            }

            // Extract just the date portion for comparison
            $searchDate = \Carbon\Carbon::parse($meta['created_at'])->format('Y-m-d');

            Log::info("Searching for duplicates for file {$index}", [
                'title' => $meta['title'],
                'department_id' => $meta['department_id'],
                'search_date' => $searchDate,
            ]);

            // Check for duplicates
            $duplicates = Document::whereRaw('LOWER(title) = ?', [strtolower($meta['title'])])
                ->where('department_id', $meta['department_id'])
                ->whereDate('created_at', $searchDate)
                ->limit(10)
                ->get(['id', 'title'])
                ->map(fn ($d) => [
                    'id' => $d->id,
                    'title' => $d->title,
                    'url' => route('documents.show', ['document' => $d->id]),
                ])
                ->toArray();

            Log::info("Duplicate search result for file {$index}", [
                'duplicates_found' => count($duplicates),
            ]);

            if (! empty($duplicates)) {
                $this->allDuplicates[$index] = $duplicates;
                $this->filesWithDuplicates[] = $index;
                Log::info("Added file {$index} to filesWithDuplicates array");
            }
        }

        Log::info('Batch duplicate check complete', [
            'total_files' => count($this->documentInfos),
            'files_with_duplicates' => count($this->filesWithDuplicates),
            'filesWithDuplicates_array' => $this->filesWithDuplicates,
        ]);

        // Show modal if any duplicates found
        if (! empty($this->filesWithDuplicates)) {
            $this->showDuplicateModal = true;
        }
    }

    // Apply shared metadata from first file to all other files
    private function applySharedMetadataToAll(): void
    {
        $firstMeta = $this->documentInfos[0] ?? null;

        if (! $firstMeta) {
            Log::warning('Cannot apply shared metadata - first file has no metadata');

            return;
        }

        Log::info('Applying shared metadata to all files', [
            'total_files' => count($this->documentInfos),
        ]);

        $sharedKeys = $this->sharedMetadataKeysToPropagate();

        // Apply to all files except the first one
        for ($i = 1; $i < count($this->documentInfos); $i++) {
            foreach ($sharedKeys as $key) {
                if (array_key_exists($key, $firstMeta)) {
                    $this->documentInfos[$i][$key] = $firstMeta[$key];
                }
            }

            // Ensure title exists (use filename if missing)
            if (empty($this->documentInfos[$i]['title']) && isset($this->documents[$i])) {
                $this->documentInfos[$i]['title'] = pathinfo($this->documents[$i]->getClientOriginalName(), PATHINFO_FILENAME);
            }
        }

        Log::info('Shared metadata applied successfully');
    }

    /**
     * Metadata fields (except title) propagated when "same metadata for all" is enabled
     * or when applying current metadata to every file.
     */
    private function sharedMetadataKeysToPropagate(): array
    {
        return [
            'category_id',
            'subcategory_id',
            'color',
            'created_at',
            'expire_at',
            'tag_ids',
            'new_tag_names',
            'physical_location_id',
            'box_id',
            'digital_only',
            'author',
            'email',
            'department_id',
            'sub_department_id',
            'service_id',
        ];
    }

    /**
     * Keep room / row / shelf / box dropdowns in sync when switching files.
     */
    private function syncPhysicalLocationSelectorsFromCurrentInfo(): void
    {
        $digital = filter_var($this->currentInfo['digital_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        if ($digital) {
            $this->selectedRoomId = null;
            $this->selectedRowId = null;
            $this->selectedShelfId = null;
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;

            return;
        }

        $boxId = $this->currentInfo['box_id'] ?? null;

        if (! $boxId) {
            $this->selectedRoomId = null;
            $this->selectedRowId = null;
            $this->selectedShelfId = null;
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;

            return;
        }

        $box = Box::query()->with(['shelf.row.room'])->find((int) $boxId);

        if (! $box?->shelf?->row) {
            return;
        }

        $row = $box->shelf->row;
        $this->selectedRoomId = $row->room_id;
        $this->selectedRowId = $row->id;
        $this->selectedShelfId = $box->shelf_id;
        $this->selectedBoxId = $box->id;
    }

    // Helper to save the form data back to the main array
    private function saveCurrentInfo()
    {
        if (! isset($this->documentInfos[$this->currentDocumentIndex])) {
            return;
        }

        if ($this->useSharedMetadata && count($this->documentInfos) > 1) {
            $keys = $this->sharedMetadataKeysToPropagate();
            foreach ($this->documentInfos as $i => $meta) {
                $title = $meta['title'] ?? null;
                foreach ($keys as $key) {
                    if (array_key_exists($key, $this->currentInfo)) {
                        $this->documentInfos[$i][$key] = $this->currentInfo[$key];
                    }
                }
                if ($title !== null) {
                    $this->documentInfos[$i]['title'] = $title;
                }
            }

            return;
        }

        $this->documentInfos[$this->currentDocumentIndex] = $this->currentInfo;
    }

    /**
     * Copy metadata from the current form to every file (titles unchanged).
     */
    public function applyCurrentMetadataToAllDocuments(): void
    {
        if (empty($this->documentInfos) || empty($this->currentInfo)) {
            return;
        }

        $this->saveCurrentInfo();

        if (! $this->useSharedMetadata || count($this->documentInfos) === 1) {
            $keys = $this->sharedMetadataKeysToPropagate();
            $this->documentInfos[$this->currentDocumentIndex] = $this->currentInfo;

            foreach ($this->documentInfos as $i => $_) {
                if ($i === $this->currentDocumentIndex) {
                    continue;
                }
                $title = $this->documentInfos[$i]['title'] ?? null;
                foreach ($keys as $key) {
                    if (array_key_exists($key, $this->currentInfo)) {
                        $this->documentInfos[$i][$key] = $this->currentInfo[$key];
                    }
                }
                if ($title !== null) {
                    $this->documentInfos[$i]['title'] = $title;
                }
            }
        }

        $this->loadCurrentInfo();
    }

    public function applyCurrentInfoToIncomplete(): void
    {
        if (empty($this->documentInfos) || empty($this->currentInfo)) {
            return;
        }

        // Keep title per file, only propagate metadata values.
        $copyKeys = [
            'category_id',
            'subcategory_id',
            'color',
            'created_at',
            'expire_at',
            'tag_ids',
            'new_tag_names',
            'box_id',
            'digital_only',
        ];

        foreach ($this->documentInfos as $index => $meta) {
            if ($index === $this->currentDocumentIndex) {
                $this->documentInfos[$index] = $this->currentInfo;

                continue;
            }

            foreach ($copyKeys as $key) {
                $sourceValue = $this->currentInfo[$key] ?? null;
                $targetValue = $meta[$key] ?? null;

                if ($this->isMetadataMissing($targetValue) && ! $this->isMetadataMissing($sourceValue)) {
                    $this->documentInfos[$index][$key] = $sourceValue;
                }
            }
        }

        $this->loadCurrentInfo();
    }

    private function isMetadataMissing(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return count($value) === 0;
        }

        return false;
    }

    public function updatedNewDocuments($value = null)
    {
        try {
            // Livewire v3 passes the new value into this hook for file inputs bound with
            // wire:model. Use that when provided (single or multiple files), otherwise
            // fall back to the property value.
            $files = $value ?? $this->newDocuments;

            if (! is_array($files)) {
                $files = $files ? [$files] : [];
            }

            // Log the number of files being processed
            Log::info('Processing file upload', [
                'file_count' => count($files),
                'user_id' => auth()->id(),
            ]);

            if (! empty($files)) {
                $this->mergeNewFiles($files);
            }

            // Clear staging property so it is ready for the next selection.
            $this->newDocuments = [];
        } catch (\Exception $e) {
            Log::error('Error processing uploaded files', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id(),
            ]);

            $this->addError('upload', 'Failed to process uploaded files. Please try again or contact support if the problem persists.');
        }
    }

    private function mergeNewFiles($files): void
    {
        // PERFORMANCE OPTIMIZATION: Pre-define filters for faster validation
        static $systemFiles = ['.DS_Store', 'Thumbs.db', 'desktop.ini', '._.DS_Store'];

        // Filter out invalid files before adding them
        $validFiles = [];
        foreach ($files as $file) {
            // Skip if not a valid uploaded file object
            if (! is_object($file) || ! method_exists($file, 'getClientOriginalName')) {
                continue;
            }

            $filename = $file->getClientOriginalName();

            // Early return optimizations: combine checks to reduce processing
            if (
                empty($filename) ||
                trim($filename) === '' ||
                str_starts_with($filename, '.') ||
                in_array($filename, $systemFiles, true)
            ) {
                continue;
            }

            // Skip if file has no extension (likely a folder or invalid file)
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            if (empty($extension)) {
                continue;
            }

            // Validate that file size is greater than 0 (single check)
            if (method_exists($file, 'getSize') && $file->getSize() <= 0) {
                continue;
            }

            // Check against max file size from config
            $maxSizeKb = config('uploads.max_file_size_kb', 50000);
            if (method_exists($file, 'getSize') && $file->getSize() > ($maxSizeKb * 1024)) {
                $this->addError('documents', __('File ":name" exceeds the maximum size of :max MB.', [
                    'name' => $filename,
                    'max' => floor($maxSizeKb / 1024),
                ]));

                continue;
            }

            $validFiles[] = $file;
        }

        // Add only valid files to documents array
        foreach ($validFiles as $file) {
            $this->documents[] = $file;
        }

        $oldDocumentInfos = $this->documentInfos;

        foreach ($this->documents as $index => $file) {
            if (! isset($oldDocumentInfos[$index])) {
                // Prefer relative path (for folders) as default title; fall back to filename
                $relative = $this->relativePaths[$index] ?? $file->getClientOriginalName();

                // For folder uploads, extract just the filename from the relative path
                // Remove folder names from the path to get clean file title
                if (isset($this->relativePaths[$index])) {
                    // Get the filename from the path (last segment)
                    $relative = basename($relative);
                }

                $baseName = pathinfo($relative, PATHINFO_FILENAME);

                $oldDocumentInfos[$index] = [
                    'title' => $baseName,
                    'category_id' => null,
                    'subcategory_id' => null,
                    'color' => null,
                    'created_at' => null,
                    'expire_at' => null,
                    'tag_ids' => [],
                    'new_tag_names' => [],
                    'physical_location_id' => null,
                    'box_id' => null,
                    'box_folder_id' => null,
                    'author' => '',
                    'email' => '',
                    'department_id' => null,
                    'sub_department_id' => null,
                    'service_id' => null,
                    'digital_only' => false,
                ];
            }
        }
        $this->documentInfos = $oldDocumentInfos;

        $this->syncDocumentOriginalNamesFromUploads();

        // After updating the main array, load the current info into the form
        $this->loadCurrentInfo();
        $this->updateCurrentPreview();
    }

    private function syncDocumentOriginalNamesFromUploads(): void
    {
        $names = [];
        foreach ($this->documents as $i => $file) {
            if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                $names[$i] = (string) $file->getClientOriginalName();
            }
        }
        $this->documentOriginalNames = $names;
    }

    protected function getStep2Rules(): array
    {
        $isDigitalOnly = filter_var($this->currentInfo['digital_only'] ?? false, FILTER_VALIDATE_BOOLEAN);

        return [
            'currentInfo.title' => 'required|string|max:255',
            'currentInfo.category_id' => 'required|exists:categories,id',
            // Subcategory is now optional; can be omitted when not relevant
            'currentInfo.subcategory_id' => 'nullable|exists:subcategories,id',
            'currentInfo.color' => 'required|string',
            'currentInfo.created_at' => 'required|date_format:Y-m-d\TH:i',
            'currentInfo.expire_at' => 'required|date|after:currentInfo.created_at',
            'currentInfo.tag_ids' => 'array',
            'currentInfo.tag_ids.*' => 'integer|exists:tags,id',
            'currentInfo.new_tag_names' => 'array',
            'currentInfo.new_tag_names.*' => 'string|max:255',
            'currentInfo.digital_only' => 'nullable|boolean',
            'currentInfo.box_id' => $isDigitalOnly ? 'nullable|exists:boxes,id' : 'required|exists:boxes,id',
            'currentInfo.author' => 'required|string|max:255',
            'currentInfo.email' => 'nullable|email|max:255',
            'currentInfo.department_id' => 'nullable|exists:departments,id',
            'currentInfo.sub_department_id' => 'nullable|exists:sub_departments,id',
            'currentInfo.service_id' => 'nullable|exists:services,id',
            // newFolderName is optional, validated globally when submitting the last document
        ];
    }

    private function recalculateExpiry(): void
    {
        $categoryId = $this->currentInfo['category_id'] ?? null;
        $createdAt = $this->currentInfo['created_at'] ?? null;
        if (! $categoryId || ! $createdAt) {
            return;
        }

        $category = Category::find($categoryId);
        if (! $category) {
            return;
        }

        $value = $category->expiry_value;
        $unit = $category->expiry_unit; // days|months|years
        if (! $value || ! $unit) {
            return;
        }

        try {
            $base = \Carbon\Carbon::parse($createdAt);
            switch ($unit) {
                case 'days':
                    $expire = $base->copy()->addDays($value);
                    break;
                case 'months':
                    $expire = $base->copy()->addMonthsNoOverflow($value);
                    break;
                case 'years':
                    $expire = $base->copy()->addYearsNoOverflow($value);
                    break;
                default:
                    return;
            }
            $this->currentInfo['expire_at'] = $expire->format('Y-m-d');
        } catch (\Throwable $e) {
            // ignore parse errors
        }
    }

    public function updatedCurrentInfoCreatedAt(): void
    {
        $this->recalculateExpiry();
    }

    public function updatedCurrentInfoDepartmentId(): void
    {
        // Org hierarchy is not user-editable; values come from account context only.
    }

    public function updatedCurrentInfoSubDepartmentId(): void {}

    public function updatedCurrentInfoServiceId(): void {}

    public function updatedCurrentInfoDigitalOnly(): void
    {
        $isDigitalOnly = filter_var($this->currentInfo['digital_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        if ($isDigitalOnly) {
            $this->selectedRoomId = null;
            $this->selectedRowId = null;
            $this->selectedShelfId = null;
            $this->selectedBoxId = null;
            $this->selectedBoxFolderId = null;
            $this->currentInfo['box_id'] = null;
            $this->currentInfo['box_folder_id'] = null;
        }
    }

    public function updatedCurrentInfoCategoryId(): void
    {
        $this->currentInfo['subcategory_id'] = null;
        $this->currentInfo['expire_at'] = null;

        $this->dispatch('categories-reset');
        $this->recalculateExpiry();
    }

    protected function messages()
    {
        return [
            'currentInfo.department_id.required' => 'Please select a department. You must be assigned to at least one department to upload documents.',
            'currentInfo.department_id.exists' => 'The selected department is invalid.',
        ];
    }

    /**
     * Determine if the current user is allowed to upload documents at all.
     *
     * For non-master users we consider them "assigned" if they have at least
     * one entry in the department_user pivot table. This avoids issues where
     * eager-loaded relations are empty because of model global scopes.
     */
    private function userHasAnyDepartment(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->can('view any role') || $user->can('view organization wide reports')) {
            return true;
        }

        if ($user->can('view any category')) {
            return true;
        }

        $ids = app(ProfileCategoryAccessService::class)->accessibleCategoryIdsFor($user);
        if ($ids === null) {
            return true;
        }
        if ($ids->isNotEmpty()) {
            return true;
        }

        if (DB::table('department_user')->where('user_id', $user->id)->exists()) {
            return true;
        }
        if (DB::table('sub_department_user')->where('user_id', $user->id)->exists()) {
            return true;
        }
        if (DB::table('service_user')->where('user_id', $user->id)->exists()) {
            return true;
        }
        if ($user->service_id || $user->sub_department_id) {
            return true;
        }

        return false;
    }

    private function hydrateOrgFromUserContext(): void
    {
        if (! auth()->check()) {
            return;
        }
        $this->hydrateOrgMetadata($this->currentInfo);
    }

    /**
     * Fill department / sub_department / service from the authenticated user's pivots (not from category).
     */
    private function hydrateOrgMetadata(array &$metadata): void
    {
        $user = auth()->user();
        if (! $user) {
            return;
        }

        $serviceId = (int) (DB::table('service_user')->where('user_id', $user->id)->orderBy('id')->value('service_id') ?: 0);
        $service = $serviceId ? Service::query()->find($serviceId) : null;

        if (! $service && $user->service_id) {
            $service = Service::query()->find($user->service_id);
            $serviceId = (int) ($service?->id ?: 0);
        }
        if (! $service) {
            $service = $user->services()->orderBy('services.id')->first();
            $serviceId = (int) ($service?->id ?: 0);
        }

        $subDeptId = (int) (DB::table('sub_department_user')->where('user_id', $user->id)->orderBy('id')->value('sub_department_id') ?: 0);
        if ($service) {
            $subDeptId = $subDeptId ?: (int) $service->sub_department_id;
        }
        if (! $subDeptId && $user->sub_department_id) {
            $subDeptId = (int) $user->sub_department_id;
        }

        $deptId = (int) (DB::table('department_user')->where('user_id', $user->id)->orderBy('id')->value('department_id') ?: 0);
        if ($service && ! $deptId) {
            $service->loadMissing('subDepartment');
            $deptId = (int) ($service->subDepartment?->department_id ?? 0);
        }
        if (! $deptId && $subDeptId) {
            $deptId = (int) (SubDepartment::query()->where('id', $subDeptId)->value('department_id') ?? 0);
        }

        $metadata['department_id'] = $deptId ?: null;
        $metadata['sub_department_id'] = $subDeptId ?: null;
        $metadata['service_id'] = $serviceId ?: null;
    }

    private function hydrateOrgForAllDocumentInfos(): void
    {
        foreach (array_keys($this->documentInfos) as $idx) {
            $this->hydrateOrgMetadata($this->documentInfos[$idx]);
        }
    }

    public function nextStep()
    {
        $this->validate();

        // When moving to step 2 for the first time, load the first document's info
        if ($this->step == 1) {
            $this->loadCurrentInfo();
        }

        $this->step++;
    }

    public function prevStep()
    {
        $this->step--;
    }

    public function nextDocument()
    {
        // Guard: user must be assigned to at least one department (via pivot).
        if (! $this->userHasAnyDepartment()) {
            $this->addError('department', 'You must be assigned to at least one department to upload documents. Please contact your administrator.');

            return;
        }

        $this->hydrateOrgFromUserContext();
        $this->validate($this->getStep2Rules());

        // 1. Save any changes from the form before moving
        $this->saveCurrentInfo();

        if ($this->currentDocumentIndex < count($this->documentInfos) - 1) {
            $this->currentDocumentIndex++;
            // 2. Load the new document's info into the form
            $this->loadCurrentInfo();
            $this->updateCurrentPreview();
        }
    }

    public function prevDocument()
    {
        // 1. Save any changes from the form before moving
        $this->saveCurrentInfo();

        if ($this->currentDocumentIndex > 0) {
            $this->currentDocumentIndex--;
            // 2. Load the new document's info into the form
            $this->loadCurrentInfo();
            $this->updateCurrentPreview();
        }
    }

    public function selectDocument(int $index): void
    {
        if ($index < 0 || $index >= count($this->documentInfos)) {
            return;
        }

        $this->saveCurrentInfo();
        $this->currentDocumentIndex = $index;
        $this->loadCurrentInfo();
    }

    public function removeDocument($index)
    {
        // Save info first if the user is deleting the doc they are currently viewing
        if ($index == $this->currentDocumentIndex) {
            $this->saveCurrentInfo();
        }

        unset($this->documents[$index], $this->documentInfos[$index]);
        $this->documents = array_values($this->documents);
        $this->documentInfos = array_values($this->documentInfos);
        $this->syncDocumentOriginalNamesFromUploads();

        // Re-index duplicate decisions to match new file indices (for the future if deleting is done at any point after duplicateDecisions)
        $newDecisions = [];
        foreach ($this->duplicateDecisions as $oldIdx => $decision) {
            if ($oldIdx < $index) {
                // Files before removed index keep same index
                $newDecisions[$oldIdx] = $decision;
            } elseif ($oldIdx > $index) {
                // Files after removed index shift down by 1
                $newDecisions[$oldIdx - 1] = $decision;
            }
            // Skip the removed file's decision (oldIdx == $index)
        }
        $this->duplicateDecisions = $newDecisions;

        // Adjust index if needed
        if ($this->currentDocumentIndex >= count($this->documentInfos) && count($this->documentInfos) > 0) {
            $this->currentDocumentIndex = count($this->documentInfos) - 1;
        }

        // Reload the form with the correct document's data
        $this->loadCurrentInfo();
        $this->updateCurrentPreview();
    }

    public function previewFile($index)
    {
        if (! isset($this->documents[$index])) {
            return;
        }

        $file = $this->documents[$index];

        // Build secure server-side preview URL
        $absolutePath = $file->getRealPath();
        $token = \Crypt::encryptString($absolutePath);
        $this->previewUrl = route('preview.temp', ['token' => $token, 'name' => $file->getClientOriginalName()]);
        $this->previewMime = $file->getMimeType();
        $this->previewName = $file->getClientOriginalName();

        $this->dispatch('show-preview-modal');
    }

    private function updateCurrentPreview(): void
    {
        $this->currentPreviewUrl = null;
        $this->currentPreviewType = null;
        $this->dispatch('upload-pdf-preview-clear');

        if (! isset($this->documents[$this->currentDocumentIndex])) {
            return;
        }

        $file = $this->documents[$this->currentDocumentIndex];
        $absolutePath = $file->getRealPath();
        if (! $absolutePath) {
            return;
        }

        try {
            $token = Crypt::encryptString($absolutePath);
            $this->currentPreviewUrl = route('preview.temp', [
                'token' => $token,
                'name' => $file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            $this->currentPreviewUrl = null;
        }

        $mime = $file->getMimeType();
        $ext = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (is_string($mime)) {
            if (str_starts_with($mime, 'image/')) {
                $this->currentPreviewType = 'image';
            } elseif ($mime === 'application/pdf') {
                $this->currentPreviewType = 'pdf';
            } elseif (in_array($ext, PdfConversionService::TEMP_UPLOAD_OFFICE_EXTENSIONS, true) && $this->currentPreviewUrl) {
                // Server converts Office → PDF at preview.temp; PDF.js loads the same URL.
                $this->currentPreviewType = 'pdf';
            } else {
                $this->currentPreviewType = 'other';
            }
        }

        if ($this->currentPreviewType === 'pdf' && $this->currentPreviewUrl) {
            $this->dispatch('upload-pdf-preview-url', url: $this->currentPreviewUrl);
        }
    }

    // User actions on duplicate modal
    public function uploadAnyway(): void
    {
        $this->duplicateDecisions[$this->currentDocumentIndex] = 'upload';
        $this->showDuplicateModal = false;
        $this->currentDuplicates = [];

        // After decision, continue navigation
        $this->continueAfterDuplicateDecision();
    }

    public function skipFile(): void
    {
        $this->duplicateDecisions[$this->currentDocumentIndex] = 'skip';
        $this->showDuplicateModal = false;
        $this->currentDuplicates = [];

        // After decision, continue navigation
        $this->continueAfterDuplicateDecision();
    }

    public function modifyCurrentFile(): void
    {
        // Simply close the modal and let user edit the form fields
        // They can change title, creation date, etc. to avoid the duplicate
        $this->showDuplicateModal = false;
        $this->currentDuplicates = [];

        // Clear any previous decision for this file so fresh duplicate check applies
        unset($this->duplicateDecisions[$this->currentDocumentIndex]);
    }

    // NEW: Batch action methods
    public function uploadAllAnyway(): void
    {
        Log::info('Upload all anyway - marking all duplicate files for upload');

        // Mark all files with duplicates as 'upload'
        foreach ($this->filesWithDuplicates as $index) {
            $this->duplicateDecisions[$index] = 'upload';
        }

        // Close modal and proceed with submission
        $this->showDuplicateModal = false;
        $this->allDuplicates = [];
        $this->filesWithDuplicates = [];

        // Proceed with submission
        $this->performSubmit();
    }

    public function skipAllWithDuplicates(): void
    {
        Log::info('Skip all with duplicates - marking all duplicate files for skipping');

        // Mark all files with duplicates as 'skip'
        foreach ($this->filesWithDuplicates as $index) {
            $this->duplicateDecisions[$index] = 'skip';
        }

        // Close modal and proceed with submission
        $this->showDuplicateModal = false;
        $this->allDuplicates = [];
        $this->filesWithDuplicates = [];

        // Proceed with submission
        $this->performSubmit();
    }

    public function reviewAndModify(): void
    {
        Log::info('Review and modify - user wants to manually review files');

        // Simply close the modal without making any decisions
        // User can navigate through files and modify metadata manually
        $this->showDuplicateModal = false;
        $this->allDuplicates = [];
        $this->filesWithDuplicates = [];
    }

    private function continueAfterDuplicateDecision(): void
    {
        // Save current info
        $this->saveCurrentInfo();

        // If user was trying to move to next file, do it now
        if ($this->currentDocumentIndex < count($this->documentInfos) - 1) {
            $this->currentDocumentIndex++;
            $this->loadCurrentInfo();
            $this->updateCurrentPreview();
        } else {
            // User was on the last file; trigger submit now
            $this->submitAfterAllDecisions();
        }
    }

    private function submitAfterAllDecisions(): void
    {
        // This is called after the last file's duplicate decision
        // Proceed with actual submission
        $this->performSubmit();
    }

    public function submit()
    {
        // Prevent duplicate submissions
        if ($this->isSubmitting) {
            return;
        }

        // Guard: user must be assigned to at least one department (via pivot).
        if (! $this->userHasAnyDepartment()) {
            $this->addError('department', 'You must be assigned to at least one department to upload documents. Please contact your administrator.');

            return;
        }

        $this->hydrateOrgFromUserContext();
        $this->saveCurrentInfo();

        $uploadRules = array_intersect_key($this->rules(), array_flip(['documents', 'documents.*']));
        $this->validate(array_merge($uploadRules, $this->getStep2Rules()));

        // IMPORTANT: Apply shared metadata to all files BEFORE checking for duplicates
        // This ensures that when useSharedMetadata is ON, all files get metadata from file 1
        if ($this->useSharedMetadata && count($this->documentInfos) > 1) {
            $this->applySharedMetadataToAll();
        }

        // NEW: Use batch duplicate check instead of single-file check
        // Check ALL files for duplicates before submitting
        $this->checkAllFilesForDuplicates();

        // If modal is shown (duplicates found), stop here; user must decide
        if ($this->showDuplicateModal) {
            return;
        }

        // Proceed with submission (no duplicates found)
        $this->performSubmit();
    }

    private function performSubmit()
    {
        // Set submitting flag at the start
        $this->isSubmitting = true;

        $successCount = 0;
        $skippedCount = 0;
        $hadError = false;

        $this->hydrateOrgForAllDocumentInfos();

        // Use first document's metadata as the shared source when uploading multiple files
        // ONLY when the user has opted in to shared metadata.
        $firstMeta = $this->documentInfos[0] ?? null;
        $deptId = $firstMeta['department_id'] ?? null;
        $serviceId = $firstMeta['service_id'] ?? null;
        if ($this->useSharedMetadata && $firstMeta && count($this->documents) > 1) {
            // These fields will be copied from the first document to all others.
            // The title is intentionally excluded so each file keeps its own name.
            $sharedKeys = [
                'category_id',
                'subcategory_id',
                'color',
                'created_at',
                'expire_at',
                'tag_ids',
                'new_tag_names',
                'physical_location_id',
                'box_id',
                'digital_only',
                'author',
                'email',
                'department_id',
                'sub_department_id',
                'service_id',
            ];

            foreach ($this->documentInfos as $idx => $meta) {
                if ($idx === 0) {
                    continue; // keep the first document as the source
                }
                foreach ($sharedKeys as $key) {
                    if (array_key_exists($key, $firstMeta)) {
                        $this->documentInfos[$idx][$key] = $firstMeta[$key];
                    }
                }
            }
        }

        // Determine folder: existing or create new logical folder if requested
        $targetFolderId = $this->folderId;

        if (is_null($targetFolderId) && $this->newFolderName) {
            $folder = Folder::create([
                'uid' => uuid_create(),
                'name' => $this->newFolderName,
                'parent_id' => null,
                'department_id' => $deptId,
                'service_id' => $serviceId,
                'created_by' => auth()->id(),
                'status' => 'pending',
            ]);

            $targetFolderId = $folder->id;
        }

        // Simple in-memory cache to avoid repeating the same folder queries
        $folderCache = [];

        foreach ($this->documents as $index => $file) {
            // If we have a relative path (from folder upload), build nested folders
            $documentFolderId = $targetFolderId;
            if (! empty($this->relativePaths[$index])) {
                $relativePath = $this->relativePaths[$index]; // e.g., "Top/Sub1/Sub2/file.pdf"
                $parts = explode('/', $relativePath);
                array_pop($parts); // remove filename

                $parentId = $targetFolderId; // allow nesting inside existing folder if set

                foreach ($parts as $folderName) {
                    if ($folderName === '' || $folderName === '.') {
                        continue;
                    }

                    $cacheKey = ($parentId ?? 0).'|'.$folderName;
                    if (isset($folderCache[$cacheKey])) {
                        $parentId = $folderCache[$cacheKey];

                        continue;
                    }

                    $folder = Folder::firstOrCreate([
                        'name' => $folderName,
                        'parent_id' => $parentId,
                        'created_by' => auth()->id(),
                    ], [
                        'uid' => uuid_create(),
                        'department_id' => $deptId ?? ($firstMeta['department_id'] ?? null),
                        'service_id' => $serviceId ?? ($firstMeta['service_id'] ?? null),
                        'status' => 'pending',
                    ]);

                    $parentId = $folder->id;
                    $folderCache[$cacheKey] = $parentId;
                }

                if ($parentId) {
                    $documentFolderId = $parentId;
                }
            }

            // Skip if user chose to skip this file
            if (($this->duplicateDecisions[$index] ?? null) === 'skip') {
                $skippedCount++;

                continue;
            }

            $metadata = $this->documentInfos[$index];
            $isDigitalOnly = filter_var($metadata['digital_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($isDigitalOnly) {
                $metadata['box_id'] = null;
            }

            $allTagIds = $this->resolveTagIdsForDocument($metadata);

            $extension = $file->getClientOriginalExtension();
            $filename = $metadata['title'].'_'.now()->format('His').'.'.$extension;
            // Upload file to private storage
            $filePath = Storage::disk('local')->putFileAs('', $file, $filename);
            $uid = uuid_create();

            try {
                \DB::beginTransaction();

                // Create document
                $document = Document::create([
                    'title' => $metadata['title'],
                    'uid' => $uid,
                    'folder_id' => $documentFolderId,
                    'created_by' => auth()->id(),
                    'category_id' => $metadata['category_id'],
                    'subcategory_id' => $metadata['subcategory_id'],
                    'created_at' => \Carbon\Carbon::parse($metadata['created_at']),
                    'expire_at' => isset($metadata['expire_at']) ? \Carbon\Carbon::parse($metadata['expire_at']) : null,
                    'physical_location_id' => $metadata['physical_location_id'] ?? null, // Keep for backward compatibility
                    'box_id' => $metadata['box_id'] ?? null,
                    'box_folder_id' => $metadata['box_folder_id'] ?? null,
                    // Use department selected by user (from their assigned departments)
                    'department_id' => $metadata['department_id'],
                    'service_id' => $metadata['service_id'] ?? null,
                    'metadata' => [
                        'color' => $metadata['color'],
                        'digital_only' => $isDigitalOnly,
                        // Enforce author/email from authenticated user regardless of UI
                        'author' => (auth()->user()?->full_name ?: (auth()->user()?->name ?? $metadata['author'])),
                        'email' => (auth()->user()?->email ?? $metadata['email']),
                    ],
                ]);

                if (! empty($allTagIds)) {
                    $document->tags()->attach($allTagIds);
                }

                $versionNumber = 1.0;

                // Create document version without touching the search index (search backend may be down)
                $docVersion = Document::withoutSyncingToSearch(function () use ($document, $versionNumber, $filePath, $extension) {
                    return DocumentVersion::create([
                        'document_id' => $document->id,
                        'uploaded_by' => auth()->id(),
                        'version_number' => $versionNumber,
                        'file_path' => $filePath,
                        'file_type' => getFileCategory($extension),
                    ]);
                });

                // PERFORMANCE OPTIMIZATION: PDF conversion moved to background job
                // for faster upload experience. The scheduled command will handle conversion.
                // Uncomment the following lines to restore immediate conversion:
                //
                // if (in_array($docVersion->file_type, ['doc', 'excel'], true)) {
                //     app(PdfConversionService::class)->convertToPdf($docVersion->file_path);
                // }

                // IMPORTANT: Do NOT trigger OCR here.
                // OCR will only be queued when the document is approved via
                // the standard approval workflow (single, bulk, or folder).

                \DB::commit();
                $successCount++;

            } catch (\Exception $e) {
                \DB::rollBack();

                $hadError = true;
                Log::error('Document creation failed', ['error' => $e->getMessage()]);
                $this->addError('file', 'Failed to upload document. Please try again.');
            }

        }

        // Build result message / redirect
        if ($successCount === 0) {
            // Nothing was uploaded successfully
            if ($skippedCount > 0 && ! $hadError) {
                return redirect()->route('documents.create')->with('error', "All $skippedCount file(s) were skipped. No documents uploaded.");
            }

            // At least one error occurred
            return redirect()->route('documents.create')->with('error', 'Failed to upload document(s). Please fix the errors and try again.');
        }

        $message = trans_choice('pages.upload.documents_uploaded_successfully', $successCount, ['count' => $successCount]);
        if ($skippedCount > 0) {
            $message .= ' '.trans_choice('pages.upload.files_skipped', $skippedCount, ['count' => $skippedCount]);
        }

        return redirect()->route('documents.success')->with('success', $message);
    }

    public function render()
    {
        // Filter data by user permissions
        $user = auth()->user();
        // Make sure we don't use any cached relationships
        $user->refresh();
        $user->loadMissing([
            'subDepartments',
            'services',
        ]);

        // Resolve departments for this user directly from pivot, bypassing Department global scope
        $userDeptIdsRaw = DB::table('department_user')
            ->where('user_id', $user->id)
            ->pluck('department_id');
        $userDepartmentsRaw = \App\Models\Department::withoutGlobalScopes()
            ->whereIn('id', $userDeptIdsRaw)
            ->get();

        $selectedCategoryId = $this->currentInfo['category_id'] ?? null;

        $categories = Category::query()->orderBy('name')->get();

        $subcategories = Subcategory::with('category')
            ->when($selectedCategoryId, function ($query) use ($selectedCategoryId) {
                $query->where('category_id', $selectedCategoryId);
            })
            ->orderBy('name')
            ->get();

        // Build organization options based directly on pivot tables.
        // - Master / Super Administrator: all org units.
        // - Others: only what they are assigned to via pivots (bypassing Department global scope).
        $isMasterOrSuper = $user && ($user->can('view any role') || $user->can('view organization wide reports'));

        $isDepartmentAdmin = $user && $user->can('filter audit logs by assigned departments');

        $isDivisionChief = $user && $user->can('view subdepartment scoped documents');

        if ($isMasterOrSuper) {
            $userDepartments = \App\Models\Department::withoutGlobalScopes()->orderBy('name')->get();
            $userSubDepartments = \App\Models\SubDepartment::with('department')->orderBy('name')->get();
            $userServices = \App\Models\Service::with('subDepartment.department')->orderBy('name')->get();
        } else {
            // Departments from pivot, bypassing Department global scope
            $userDepartments = $userDepartmentsRaw;                 // from department_user

            // Base sub-departments on role:
            if ($isDepartmentAdmin) {
                // Department Admin: all sub-departments in their departments
                $deptIds = $userDepartments->pluck('id');
                $userSubDepartments = SubDepartment::whereIn('department_id', $deptIds)->get();
            } else {
                // Others: explicit assignments via pivot
                $userSubDepartments = $user->subDepartments;           // from sub_department_user
            }

            // Services based on role
            if ($isDivisionChief) {
                // Division Chief: all services under their sub-departments
                $subIds = $userSubDepartments->pluck('id');
                $userServices = Service::whereIn('sub_department_id', $subIds)->get();
            } elseif ($isDepartmentAdmin) {
                // Department Admin: all services under all sub-departments in their departments
                $subIds = $userSubDepartments->pluck('id');
                $userServices = Service::whereIn('sub_department_id', $subIds)->get();
            } else {
                // Others (service roles): explicit service assignments
                $userServices = $user->services;                     // from service_user
            }
        }

        // Detailed debug logging to verify what the component sees
        Log::info('Upload org options', [
            'user_id' => $user->id ?? null,
            'raw_departments' => $user->departments?->pluck('id')->all(),
            'raw_sub_departments' => $user->subDepartments?->pluck('id')->all(),
            'raw_services' => $user->services?->pluck('id')->all(),
            'department_ids' => $userDepartments->pluck('id')->all(),
            'sub_department_ids' => $userSubDepartments->pluck('id')->all(),
            'service_ids' => $userServices->pluck('id')->all(),
        ]);

        // NOTE: Rooms, rows, shelves, and boxes are now loaded via computed properties
        // (getRoomsProperty, getRowsProperty, etc.) which automatically filter by user's service access
        // Do not load $rooms here as it will override the filtered computed property

        return view('livewire.multiple-documents-create-form', [
            'categories' => $categories,
            'subcategories' => $subcategories,
            'userDepartments' => $userDepartments,
            'userSubDepartments' => $userSubDepartments,
            'userServices' => $userServices,
            'tags' => Tag::all(),
            'canProceedUpload' => $this->userHasAnyDepartment(),
        ]);
    }
}
