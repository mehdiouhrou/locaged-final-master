<?php

namespace App\Livewire;

use App\Models\Document;
use App\Services\CollaborativeDocumentService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentAttachmentForm extends Component
{
    use WithFileUploads;

    public Document $document;
    public bool $canAdd = false;
    public string $label = '';
    public $file;

    protected function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'max:51200'],
        ];
    }

    public function submit(CollaborativeDocumentService $service)
    {
        $this->validate();

        $filePath = Storage::disk('local')->putFileAs('attachments', $this->file, $this->file->getClientOriginalName());

        try {
            $service->addAttachment(
                $this->document,
                auth()->user(),
                $this->label,
                $filePath,
                $this->file->getClientOriginalExtension()
            );
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());
            return;
        }

        $this->reset(['label', 'file']);
        $this->document->refresh();

        session()->flash('success', 'Complément ajouté.');
    }

    public function render()
    {
        return view('livewire.document-attachment-form');
    }
}
