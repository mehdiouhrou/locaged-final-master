<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\CollaborativeDocumentService;
use App\Services\PdfConversionService;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class CollaborativeDocumentCreateForm extends Component
{
    use WithFileUploads;

    public string $title = '';
    public string $description = '';
    public $file;

    public ?string $previewUrl = null;
    public ?string $previewType = null;

    public string $reviewerSearch = '';
    public array $selectedReviewers = []; // [ ['id' => int, 'full_name' => string, 'deadline' => ?string] ]

    public function mount(): void
    {
        abort_unless(\App\Support\Branding::isCollaborativeModuleEnabled(), 403, "Le module Document actif n'est pas activé sur cette instance.");
    }

    public function updatedFile(): void
    {
        $this->previewUrl = null;
        $this->previewType = null;
        $this->dispatch('upload-pdf-preview-clear');

        if (! $this->file) {
            return;
        }

        if (trim($this->title) === '') {
            $originalName = $this->file->getClientOriginalName();
            $this->title = pathinfo($originalName, PATHINFO_FILENAME);
        }

        $absolutePath = $this->file->getRealPath();
        if (! $absolutePath) {
            return;
        }

        try {
            $token = Crypt::encryptString($absolutePath);
            $this->previewUrl = route('preview.temp', [
                'token' => $token,
                'name' => $this->file->getClientOriginalName(),
            ]);
        } catch (\Throwable $e) {
            $this->previewUrl = null;
        }

        $mime = $this->file->getMimeType();
        $ext = strtolower(pathinfo($this->file->getClientOriginalName(), PATHINFO_EXTENSION));
        if (is_string($mime)) {
            if (str_starts_with($mime, 'image/')) {
                $this->previewType = 'image';
            } elseif ($mime === 'application/pdf') {
                $this->previewType = 'pdf';
            } elseif (in_array($ext, PdfConversionService::TEMP_UPLOAD_OFFICE_EXTENSIONS, true) && $this->previewUrl) {
                // Le serveur convertit Office → PDF sur preview.temp ; PDF.js charge la même URL.
                $this->previewType = 'pdf';
            } else {
                $this->previewType = 'other';
            }
        }

        if ($this->previewType === 'pdf' && $this->previewUrl) {
            $this->dispatch('upload-pdf-preview-url', url: $this->previewUrl);
        }
    }

    protected function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'], // 50 Mo, cohérent avec les uploads existants
            'selectedReviewers' => ['required', 'array', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'selectedReviewers.required' => 'Vous devez assigner au moins un relecteur.',
            'selectedReviewers.min' => 'Vous devez assigner au moins un relecteur.',
        ];
    }

    public function getSearchResultsProperty()
    {
        if (strlen($this->reviewerSearch) < 2) {
            return collect();
        }

        $selectedIds = collect($this->selectedReviewers)->pluck('id')->all();

        return User::where('full_name', 'like', '%' . $this->reviewerSearch . '%')
            ->whereNotIn('id', $selectedIds)
            ->orderBy('full_name')
            ->limit(10)
            ->get(['id', 'full_name']);
    }

    public function addReviewer(int $userId): void
    {
        $user = User::find($userId);

        if (! $user) {
            return;
        }

        $this->selectedReviewers[] = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'deadline' => null,
        ];

        $this->reviewerSearch = '';
    }

    public function removeReviewer(int $index): void
    {
        unset($this->selectedReviewers[$index]);
        $this->selectedReviewers = array_values($this->selectedReviewers);
    }

    public function submit(CollaborativeDocumentService $service)
    {
        abort_unless(\App\Support\Branding::isCollaborativeModuleEnabled(), 403, "Le module Document actif n'est pas activé sur cette instance.");

        $this->validate();

        $filePath = Storage::disk('local')->putFileAs('', $this->file, $this->file->getClientOriginalName());

        $author = auth()->user();
        $authorService = $author?->service_id
            ? \App\Models\Service::with('subDepartment')->find($author->service_id)
            : null;

        $document = Document::create([
            'uid' => (string) \Illuminate\Support\Str::uuid(),
            'title' => $this->title,
            'status' => 'brouillon',
            'entry_type' => 'collaborative',
            'created_by' => auth()->id(),
            'service_id' => $author?->service_id,
            'sub_department_id' => $authorService?->subDepartment?->id,
            'department_id' => $authorService?->subDepartment?->department_id,
        ]);

        DocumentVersion::create([
            'document_id' => $document->id,
            'file_path' => $filePath,
            'file_type' => $this->file->getClientOriginalExtension(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        if (trim($this->description) !== '') {
            \App\Models\DocumentComment::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'type' => 'initial_description',
                'comment' => $this->description,
            ]);
        }

        $reviewersPayload = collect($this->selectedReviewers)->map(fn ($r) => [
            'reviewer_id' => $r['id'],
            'deadline' => $r['deadline'] ?: null,
        ])->all();

        $service->assignReviewers($document, $reviewersPayload);

        session()->flash('success', 'Document soumis pour relecture.');

        return redirect()->route('documents.status');
    }

    public function render()
    {
        return view('livewire.collaborative-document-create-form');
    }
}
