<h5 class="mb-3"><i class="fa-solid fa-toggle-on me-2 text-primary"></i>Modules</h5>
<p class="text-muted small mb-4">
    Active ou désactive des modules optionnels pour cette instance, selon ce que le client a souscrit.
</p>

@if (session('success'))
    <div class="alert alert-success py-2 small">{{ session('success') }}</div>
@endif

<form action="{{ route('master.console.collaborative-module') }}" method="POST" class="d-flex align-items-center justify-content-between border rounded-3 p-3">
    @csrf
    @method('PUT')
    <div>
        <div class="fw-semibold">Document actif (workflow collaboratif)</div>
        <div class="text-muted small">
            Permet aux utilisateurs de soumettre un document pour relecture par des personnes nommées,
            avant archivage classique, avec compléments et historique de commentaires.
        </div>
    </div>
    <div class="form-check form-switch ms-3">
        <input type="hidden" name="enabled" value="0">
        <input class="form-check-input" type="checkbox" role="switch" name="enabled" value="1"
               id="collaborativeModuleToggle"
               {{ $collaborativeModuleEnabled ? 'checked' : '' }}
               onchange="this.form.submit()"
               style="width: 3rem; height: 1.5rem;">
    </div>
</form>
