<div class="card border-0 shadow-sm">
    <div class="card-body">
        <h6 class="text-uppercase text-muted small fw-bold mb-3">Corriger et resoumettre</h6>

        <form wire:submit.prevent="submit">
            <div class="mb-3">
                <label class="form-label">Nouveau fichier</label>
                <input type="file" class="form-control" wire:model="file">
                @error('file') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                <div wire:loading wire:target="file" class="small text-muted mt-1">Téléchargement en cours...</div>
            </div>

            <div class="mb-3">
                <label class="form-label">Commentaire (optionnel)</label>
                <textarea class="form-control" rows="2" wire:model="comment"
                          placeholder="Expliquez ce qui a été corrigé..."></textarea>
                @error('comment') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            </div>

            <button type="submit" class="btn btn-dark">
                <span wire:loading.remove wire:target="submit">Resoumettre pour relecture</span>
                <span wire:loading wire:target="submit">Envoi en cours...</span>
            </button>
        </form>
    </div>
</div>
