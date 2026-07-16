<?php

namespace App\Livewire;

use App\Enums\DocumentStatus;
use App\Models\Category;
use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Livewire\Component;

class DocumentElasticSearch extends Component
{
    public $query = '';
    public $results = [];

    public $filters = [
        'type' => '',
        'category_id' => '',
        'status' => '',
        'creation_start' => '',
        'creation_end' => '',
        'modified_start' => '',
        'modified_end' => '',
        'author' => '',
        'tags' => '',
        'box' => '',
    ];


    /**
     * React to changes on the search box and any filter field.
     *
     * This ensures that Creation Date, Tags and Author
     * (as well as File Type) immediately affect the live results
     * dropdown under the header search bar.
     */
    public function updated($propertyName)
    {
        // When the main query changes or any of the nested
        // filters (filters.*) change, re-run the search.
        if ($propertyName === 'query' || str_starts_with($propertyName, 'filters.')) {
            $this->searchDocuments();
        }
    }

    public function applyFilters()
    {
        return $this->goToDocuments();
    }

    public function resetFilters()
    {
        $this->filters = [
            'type' => '',
            'category_id' => '',
            'status' => '',
            'creation_start' => '',
            'creation_end' => '',
            'modified_start' => '',
            'modified_end' => '',
            'author' => '',
            'tags' => '',
            'box' => '',
        ];

        $this->searchDocuments(); // Optional: refresh results after reset
    }

    public function getActiveFiltersCountProperty()
    {
        return collect($this->filters)->filter(fn($value) => !empty($value))->count();
    }


    private function searchDocuments()
    {
        $term = trim((string) $this->query);
        if (strlen($term) < 2) {
            $this->results = [];
            return;
        }

        try {
            $searchResults = Document::search($term)->take(50)->get();
            $searchResults->loadMissing(['latestVersion', 'tags', 'createdBy']);
        } catch (\Throwable $e) {
            Log::warning('Header search: Scout failed, using SQL fallback', [
                'query' => $term,
                'error' => $e->getMessage(),
            ]);
            $like = '%'.strtolower($term).'%';
            $searchResults = Document::with(['latestVersion', 'tags', 'createdBy'])
                ->where(function ($q) use ($like) {
                    $q->whereRaw('LOWER(title) LIKE ?', [$like])
                        ->orWhereHas('latestVersion', function ($vq) use ($like) {
                            $vq->whereRaw('LOWER(ocr_text) LIKE ?', [$like]);
                        });
                })
                ->orderByDesc('updated_at')
                ->limit(50)
                ->get();
        }

        $searchResults->loadMissing(['latestVersion', 'tags', 'createdBy']);

        $this->results = $searchResults->filter(function (Document $doc) {
            // FILTER: Type (based on file extension)
            if ($this->filters['type']) {
                $path = $doc->latestVersion?->file_path ?? '';
                $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

                $typeExtensions = [
                    'pdf' => ['pdf'],
                    'doc' => ['doc', 'docx'],
                    'image' => ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
                    'excel' => ['xls', 'xlsx', 'csv'],
                    'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', 'webm', 'mkv'],
                    'audio' => ['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a'],
                ];

                if (! isset($typeExtensions[$this->filters['type']]) || ! in_array($ext, $typeExtensions[$this->filters['type']])) {
                    return false;
                }
            }

            if (! empty($this->filters['category_id'])) {
                $docCategoryId = (int) ($doc->category_id ?? 0);
                if ($docCategoryId !== (int) $this->filters['category_id']) {
                    return false;
                }
            }

            if (! empty($this->filters['status'])) {
                $docStatus = (string) ($doc->status ?? '');
                if ((string) $this->filters['status'] === 'expired') {
                    $isExpired = (bool) ($doc->is_expired ?? false)
                        || (optional($doc->expire_at)?->isPast() ?? false);
                    if (! $isExpired) {
                        return false;
                    }
                } elseif ($docStatus !== (string) $this->filters['status']) {
                    return false;
                }
            }

            if ($this->filters['creation_start'] && optional($doc->created_at)->lt($this->filters['creation_start'])) {
                return false;
            }

            if ($this->filters['creation_end'] && optional($doc->created_at)->gt($this->filters['creation_end'])) {
                return false;
            }

            if ($this->filters['modified_start'] && optional($doc->latestVersion?->updated_at)->lt($this->filters['modified_start'])) {
                return false;
            }

            if ($this->filters['modified_end'] && optional($doc->latestVersion?->updated_at)->gt($this->filters['modified_end'])) {
                return false;
            }

            if ($this->filters['author']) {
                $authorFilter = strtolower($this->filters['author']);
                $fullName = strtolower($doc->createdBy->full_name ?? '');
                $email = strtolower($doc->createdBy->email ?? '');
                $metadataAuthor = strtolower($doc->metadata['author'] ?? '');

                if (
                    ! str_contains($fullName, $authorFilter) &&
                    ! str_contains($email, $authorFilter) &&
                    ! str_contains($metadataAuthor, $authorFilter)
                ) {
                    return false;
                }
            }

            $filterTags = array_filter(array_map('trim', explode(',', strtolower($this->filters['tags'] ?? ''))));

            if (! empty($filterTags)) {
                $docTags = $doc->tags->pluck('name')->map(fn ($t) => strtolower($t))->toArray();
                if (! collect($filterTags)->every(fn ($tag) => in_array($tag, $docTags))) {
                    return false;
                }
            }

            return true;
        })->values();
    }

    public function highlightedTitle(?string $title): string
    {
        $safeTitle = trim((string) $title);
        if ($safeTitle === '') {
            return '';
        }

        return $this->highlightText(Str::limit($safeTitle, 90));
    }

    public function highlightedSnippet(?string $ocrText): string
    {
        $text = trim((string) $ocrText);
        if ($text === '') {
            return '';
        }

        $term = trim((string) $this->query);
        if ($term === '') {
            return e(Str::limit($text, 110));
        }

        $position = mb_stripos($text, $term);
        if ($position === false) {
            return e(Str::limit($text, 110));
        }

        $start = max(0, $position - 45);
        $snippet = mb_substr($text, $start, 120);

        if ($start > 0) {
            $snippet = '...'.$snippet;
        }
        if (($start + mb_strlen($snippet)) < mb_strlen($text)) {
            $snippet .= '...';
        }

        return $this->highlightText($snippet);
    }

    private function highlightText(string $text): string
    {
        $term = trim((string) $this->query);
        if ($term === '') {
            return e($text);
        }

        $escapedTerm = preg_quote($term, '/');
        $parts = preg_split('/('.$escapedTerm.')/iu', $text, -1, PREG_SPLIT_DELIM_CAPTURE);
        if (! is_array($parts)) {
            return e($text);
        }

        $out = '';
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            if (mb_strtolower($part) === mb_strtolower($term)) {
                $out .= '<mark>'.e($part).'</mark>';
            } else {
                $out .= e($part);
            }
        }

        return $out;
    }

    public function goToDocuments()
    {
        // Map header filters to DocumentsTable query params
        $fileType = $this->filters['type'] ?? '';

        $params = [
            'search'   => $this->query ?: null,
            'fileType' => $fileType ?: null,
            'category' => $this->filters['category_id'] ?: null,
            'status'   => $this->filters['status'] ?: null,
            'dateFrom' => $this->filters['creation_start'] ?: null,
            'dateTo'   => $this->filters['creation_end'] ?: null,
            'author'   => $this->filters['author'] ?: null,
            'tags'     => $this->filters['tags'] ?: null,
            'box_q'    => $this->filters['box'] ?: null,
        ];

        // Remove nulls
        $params = array_filter($params, function ($v) { return !is_null($v) && $v !== ''; });

        return redirect()->to(route('documents.all', $params));
    }

    public function render()
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);
        $statuses = array_map(fn (DocumentStatus $s) => $s->value, DocumentStatus::activeCases());

        return view('livewire.document-elastic-search', [
            'categories' => $categories,
            'statuses' => $statuses,
        ]);
    }
}
