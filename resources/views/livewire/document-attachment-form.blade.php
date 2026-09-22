<div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
        <h6 class="fw-bold mb-3">{{ __('Compléments de document') }}</h6>

        @if (session('error'))
            <div class="alert alert-danger py-2 small">{{ session('error') }}</div>
        @endif

        @if ($canAdd)
            <form wire:submit.prevent="submit">
                <div class="mb-2">
                    <label class="form-label small">{{ __('Description') }}</label>
                    <input type="text" class="form-control form-control-sm" wire:model="label"
                           placeholder="{{ __('Ex : preuve de paiement, lettre annexe...') }}">
                    @error('label') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-2">
                    <label class="form-label small">{{ __('Fichier') }}</label>
                    <input type="file" class="form-control form-control-sm" wire:model="file">
                    @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="file" class="small text-muted mt-1">{{ __('Téléchargement en cours...') }}</div>
                </div>

                <button type="submit" class="btn btn-sm btn-outline-dark">
                    <span wire:loading.remove wire:target="submit">{{ __('Ajouter') }}</span>
                    <span wire:loading wire:target="submit">{{ __('Envoi...') }}</span>
                </button>
            </form>
        @endif

        @if ($document->attachments->isNotEmpty())
            <hr>
            <ul class="list-group list-group-flush mt-2">
                @foreach ($document->attachments as $attachment)
                    <li class="list-group-item px-0 py-2 border-0 border-bottom d-flex justify-content-between align-items-center">
                        <div>
                            <span class="fw-semibold small">{{ $attachment->label }}</span>
                            <span class="text-muted small d-block">
                                {{ $attachment->uploadedBy?->full_name ?? $attachment->uploadedBy?->name ?? __('Inconnu') }}
                                — {{ $attachment->uploaded_at?->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        <a href="{{ route('document-attachments.download', $attachment->id) }}" class="btn btn-sm btn-outline-secondary">
                            {{ __('Télécharger') }}
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
