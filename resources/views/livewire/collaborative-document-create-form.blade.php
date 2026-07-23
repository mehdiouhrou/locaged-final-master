<div class="container-fluid py-4">
    <h4 class="mb-4">Nouveau document actif</h4>

    <div class="row g-4">
        <div class="col-lg-6">
            <form wire:submit.prevent="submit">
                <div class="mb-3">
                    <label class="form-label">Titre du document</label>
                    <input type="text" class="form-control" wire:model="title">
                    @error('title') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                <div class="mb-3">
                    <label class="form-label">Fichier</label>
                    <input type="file" class="form-control" wire:model="file">
                    @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    <div wire:loading wire:target="file" class="small text-muted mt-1">Téléchargement en cours...</div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Relecteurs</label>
                    <input type="text" class="form-control" placeholder="Rechercher un utilisateur par nom..."
                           wire:model.live.debounce.400ms="reviewerSearch">

                    @if (strlen($reviewerSearch) >= 2 && $this->searchResults->isNotEmpty())
                        <div class="list-group mt-1">
                            @foreach ($this->searchResults as $result)
                                <button type="button" class="list-group-item list-group-item-action"
                                        wire:click="addReviewer({{ $result->id }})">
                                    {{ $result->full_name }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @error('selectedReviewers') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                </div>

                @if (count($selectedReviewers) > 0)
                    <div class="mb-3">
                        <label class="form-label">Relecteurs assignés</label>
                        <table class="table table-sm align-middle">
                            <thead>
                                <tr>
                                    <th>Nom</th>
                                    <th>Délai (optionnel)</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($selectedReviewers as $index => $reviewer)
                                    <tr>
                                        <td>{{ $reviewer['full_name'] }}</td>
                                        <td>
                                            <input type="date" class="form-control form-control-sm"
                                                   wire:model="selectedReviewers.{{ $index }}.deadline">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                    wire:click="removeReviewer({{ $index }})">
                                                Retirer
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <button type="submit" class="btn btn-dark">
                    <span wire:loading.remove wire:target="submit">Soumettre pour relecture</span>
                    <span wire:loading wire:target="submit">Envoi en cours...</span>
                </button>
            </form>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm" style="height: 80vh;">
                <div class="card-body" style="height: 100%; overflow-y: auto;">
                    @if ($previewType === 'pdf')
                        <div id="upload-pdfjs-viewer"></div>
                    @elseif ($previewType === 'image' && $previewUrl)
                        <img src="{{ $previewUrl }}" class="img-fluid rounded" alt="Aperçu">
                    @elseif ($previewType === 'other')
                        <p class="text-muted text-center py-5">Aperçu non disponible pour ce type de fichier.</p>
                    @else
                        <p class="text-muted text-center py-5">Sélectionnez un fichier pour voir l'aperçu.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
