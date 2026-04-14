<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; color: #111; }
        h1 { font-size: 16pt; margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; vertical-align: top; }
        th { background: #f0f0f0; width: 32%; }
        .muted { font-size: 9pt; color: #444; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>Procès-verbal de destruction documentaire</h1>
    <p><strong>Référence PV :</strong> {{ $certificate->public_id }}</p>
    <p><strong>Date d’émission :</strong> {{ now()->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>

    <table>
        <tr><th>Intitulé du document</th><td>{{ $document->title }}</td></tr>
        <tr><th>Identifiant document (UID)</th><td>{{ $document->uid }}</td></tr>
        <tr><th>Empreinte fichier (hash)</th><td>{{ $document->file_hash ?? '—' }}</td></tr>
        <tr><th>Pôle / département</th><td>{{ $manifest['department'] ?? '—' }}</td></tr>
        <tr><th>Service / cellule</th><td>{{ $manifest['service'] ?? '—' }}</td></tr>
        <tr><th>Date constat destruction</th><td>{{ \Illuminate\Support\Carbon::parse($manifest['destroyed_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td></tr>
        <tr><th>Approbateur</th><td>{{ $manifest['approved_by'] ?? '—' }}</td></tr>
    </table>

    <p class="muted">{{ $manifest['retention_note'] ?? '' }}</p>
</body>
</html>
