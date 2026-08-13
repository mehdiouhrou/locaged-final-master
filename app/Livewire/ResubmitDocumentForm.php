<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Services\CollaborativeDocumentService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class ResubmitDocumentForm extends Component
{
    use WithFileUploads;

    public Document $document;
    public $file;
    public string $comment = '';

    protected function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:51200'],
            'comment' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function submit(CollaborativeDocumentService $service)
    {
        if (! in_array($this->document->status, ['brouillon'], true)) {
            session()->flash('error', 'Ce document ne peut plus être resoumis (statut actuel non modifiable).');
            return redirect()->route('documents.active');
        }

        $this->validate();

        $filePath = Storage::disk('local')->putFileAs('', $this->file, $this->file->getClientOriginalName());

        DocumentVersion::create([
            'document_id' => $this->document->id,
            'file_path' => $filePath,
            'file_type' => $this->file->getClientOriginalExtension(),
            'uploaded_by' => auth()->id(),
            'uploaded_at' => now(),
        ]);

        $service->resubmit($this->document, $this->comment);

        session()->flash('success', 'Document resoumis pour relecture.');

        return redirect()->route('documents.active');
    }

    public function render()
    {
        return view('livewire.resubmit-document-form');
    }
}
