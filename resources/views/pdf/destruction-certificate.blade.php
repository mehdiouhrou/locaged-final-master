<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10.5pt; color: #1a1a1a; line-height: 1.35; }
        .pv-header { border-bottom: 1px solid #333; padding-bottom: 10px; margin-bottom: 16px; }
        .pv-header-inner { display: table; width: 100%; }
        .pv-logo-cell { vertical-align: middle; width: 38%; }
        .pv-title-cell { vertical-align: middle; text-align: right; }
        .pv-logo { max-height: 48px; max-width: 220px; }
        h1 { font-size: 14pt; font-weight: bold; margin: 0 0 4px 0; letter-spacing: 0.02em; }
        .pv-sub { font-size: 8.5pt; color: #444; margin: 0; }
        table.meta { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.meta th, table.meta td { border: 1px solid #222; padding: 7px 9px; text-align: left; vertical-align: top; }
        table.meta th { background: #f4f4f4; font-weight: 600; width: 34%; font-size: 9.5pt; }
        table.meta td { font-size: 10pt; }
        .muted { font-size: 8.5pt; color: #444; margin-top: 18px; line-height: 1.4; }
        .loc-line { margin: 2px 0; }
    </style>
</head>
<body>
@php
    $snap = $certificate->physical_location_snapshot ?? ($manifest['physical_location_snapshot'] ?? ['type' => 'numerique']);
    $category = $document->category;
@endphp

    <div class="pv-header">
        <table class="pv-header-inner" style="border: none; width: 100%; border-collapse: collapse;">
            <tr>
                <td class="pv-logo-cell" style="border: none; padding: 0;">
                    @if(!empty($clientLogoDataUri))
                        <img class="pv-logo" src="{{ $clientLogoDataUri }}" alt="" />
                    @endif
                </td>
                <td class="pv-title-cell" style="border: none; padding: 0;">
                    <h1>{{ !empty($manifest['permanent_deletion']) ? 'Procès-verbal de suppression définitive' : 'Procès-verbal de destruction documentaire' }}</h1>
                    <p class="pv-sub">{{ config('app.name', 'LocaGed') }} — document officiel de traçabilité</p>
                </td>
            </tr>
        </table>
    </div>

    <p style="margin: 0 0 10px 0;"><strong>Référence PV :</strong> {{ $certificate->public_id }}</p>
    <p style="margin: 0 0 10px 0;"><strong>Date d’émission :</strong> {{ now()->timezone(config('app.timezone'))->format('d/m/Y à H:i') }}</p>

    <table class="meta">
        <tr><th>Intitulé du document</th><td>{{ $document->title }}</td></tr>
        <tr><th>Identifiant document (UID)</th><td>{{ $document->uid }}</td></tr>
        <tr><th>Empreinte fichier (hash)</th><td>{{ $document->file_hash ?? '—' }}</td></tr>
        <tr><th>Date d’ajout du document</th><td>{{ $document->created_at ? $document->created_at->timezone(config('app.timezone'))->format('d/m/Y') : '—' }}</td></tr>
        <tr><th>Date d’expiration</th><td>{{ $document->expire_at ? \Illuminate\Support\Carbon::parse($document->expire_at)->format('d/m/Y') : '—' }}</td></tr>
        <tr><th>Dossier</th><td>{{ $category?->name ?? '—' }}</td></tr>
        <tr><th>Règle de conservation</th><td>{{ $category ? $category->retentionSummary() : '—' }}</td></tr>
        <tr>
            <th>Emplacement physique</th>
            <td>
                @if(($snap['type'] ?? '') === 'numerique')
                    Document numérique uniquement
                @else
                    <div class="loc-line"><strong>Salle :</strong> {{ $snap['room'] ?? '—' }}</div>
                    <div class="loc-line"><strong>Rangée :</strong> {{ $snap['row'] ?? '—' }}</div>
                    <div class="loc-line"><strong>Étagère :</strong> {{ $snap['shelf'] ?? '—' }}</div>
                    <div class="loc-line"><strong>Boîte :</strong> {{ $snap['box'] ?? '—' }}</div>
                @endif
            </td>
        </tr>
        <tr><th>{{ !empty($manifest['permanent_deletion']) ? 'Date de la suppression' : 'Date constat destruction' }}</th><td>{{ \Illuminate\Support\Carbon::parse($manifest['destroyed_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td></tr>
        <tr><th>{{ !empty($manifest['permanent_deletion']) ? 'Effectué par' : 'Approbateur' }}</th><td>{{ $manifest['approved_by'] ?? '—' }}</td></tr>
    </table>

    <p class="muted">{{ $manifest['retention_note'] ?? '' }}</p>
</body>
</html>
