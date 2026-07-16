@php
    $reversibilityCategories = \App\Models\Category::orderBy('name')->get(['id', 'name']);
@endphp

<h5 class="mb-3"><i class="fa-solid fa-box-archive me-2 text-primary"></i>Export réversibilité</h5>
<p class="text-muted small mb-4">
    Génère une archive ZIP contenant les fichiers originaux et un fichier CSV avec toutes les métadonnées
    (catégorie, dates, hash SHA-256, emplacement physique, nom de boîte, tags). Utile pour un export
    complet ou une réponse à une exigence de réversibilité contractuelle.
</p>

@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $error)
            <div>{{ $error }}</div>
        @endforeach
    </div>
@endif

<form action="{{ route('master.export-reversibility') }}" method="GET" class="row g-3">
    <div class="col-md-4">
        <label class="form-label small">Catégorie</label>
        <select name="category_id" class="form-select">
            <option value="">Toutes</option>
            @foreach($reversibilityCategories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label small">Statut</label>
        <select name="status" class="form-select">
            <option value="">Tous</option>
            <option value="approved">Approuvé</option>
            <option value="pending">En attente</option>
            <option value="declined">Refusé</option>
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label small">Recherche (titre)</label>
        <input type="text" name="search" class="form-control" placeholder="Filtrer par titre...">
    </div>

    <div class="col-md-3">
        <label class="form-label small">Date de création — du</label>
        <input type="date" name="date_from" class="form-control">
    </div>

    <div class="col-md-3">
        <label class="form-label small">Date de création — au</label>
        <input type="date" name="date_to" class="form-control">
    </div>

    <div class="col-md-6 d-flex align-items-end">
        <button type="submit" class="btn btn-dark">
            <i class="fa-solid fa-file-zipper me-2"></i>Générer l'export
        </button>
        <span class="text-muted small ms-3">
            Sans filtre, l'export couvre tous les documents. Peut prendre du temps si le volume est important.
        </span>
    </div>
</form>
