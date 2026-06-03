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
        table.validation { width: 100%; border-collapse: collapse; margin-top: 14px; }
        table.validation th { background: #f4f4f4; font-weight: 600; font-size: 9.5pt; border: 1px solid #222; padding: 6px 9px; text-align: left; }
        table.validation td { border: 1px solid #222; padding: 6px 9px; font-size: 10pt; vertical-align: top; }
        .section-title { font-size: 11pt; font-weight: bold; margin: 22px 0 6px 0; border-bottom: 1px solid #aaa; padding-bottom: 4px; }
        .corps { border: 1px solid #ccc; border-radius: 4px; padding: 16px 20px; margin-top: 18px; background: #fafafa; line-height: 1.6; }
        .motif-block { margin-top: 14px; font-weight: bold; }
        .signatures { width: 100%; margin-top: 40px; border-collapse: collapse; }
        .signatures td { width: 50%; padding: 0 20px; vertical-align: top; text-align: center; border: none; }
        .sig-line { border-top: 1px dashed #555; margin-top: 48px; padding-top: 6px; font-size: 9.5pt; color: #444; }
        .footer { font-size: 8pt; color: #888; margin-top: 24px; border-top: 1px solid #ddd; padding-top: 6px; text-align: center; }
        .amount-words { font-style: italic; color: #333; }
    </style>
</head>
<body>

    <div class="pv-header">
        <table class="pv-header-inner" style="border: none; width: 100%; border-collapse: collapse;">
            <tr>
                <td class="pv-logo-cell" style="border: none; padding: 0;">
                    @if(!empty($clientLogoDataUri))
                        <img class="pv-logo" src="{{ $clientLogoDataUri }}" alt="" />
                    @endif
                </td>
                <td class="pv-title-cell" style="border: none; padding: 0;">
                    <h1>Mise à disposition</h1>
                    <p class="pv-sub">{{ config('app.name', 'LocaGed') }} — document officiel de paiement</p>
                </td>
            </tr>
        </table>
    </div>

    <p style="margin: 0 0 4px 0;"><strong>Référence :</strong> {{ $document->uid }}</p>
    <p style="margin: 0 0 10px 0;"><strong>Date d'émission :</strong> {{ now()->timezone(config('app.timezone'))->format('d/m/Y à H:i') }}</p>

    <table class="meta">
        <tr>
            <th>Référence document</th>
            <td>{{ $document->uid }}</td>
        </tr>
        <tr>
            <th>Objet / Intitulé</th>
            <td>{{ $document->title }}</td>
        </tr>
        <tr>
            <th>Bénéficiaire</th>
            <td>{{ $manifest['supplier'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>N° de compte</th>
            <td>{{ $manifest['account_number'] ?? '—' }}</td>
        </tr>
        <tr>
            <th>Montant (chiffres)</th>
            <td><strong>{{ $manifest['amount'] !== null ? number_format((float) $manifest['amount'], 2, ',', ' ') . ' DH' : '—' }}</strong></td>
        </tr>
        <tr>
            <th>Montant (lettres)</th>
            <td class="amount-words">{{ $manifest['amount_words'] ?? '—' }}</td>
        </tr>
    </table>

    <div class="corps">
        Par le débit de notre compte N° <strong>{{ $manifest['account_number'] ?? '—' }}</strong>,
        nous vous prions de bien vouloir mettre à disposition de
        <strong>{{ $manifest['supplier'] ?? '—' }}</strong>
        un montant de
        <strong>{{ $manifest['amount'] !== null ? number_format((float) $manifest['amount'], 2, ',', ' ') : '—' }} DH</strong>
        (<span class="amount-words">{{ $manifest['amount_words'] ?? '—' }}</span>).

        @if(!empty($manifest['reason']))
        <div class="motif-block">Motif : {{ $manifest['reason'] }}</div>
        @endif
    </div>

    <p class="section-title">Circuit de validation</p>

    <table class="validation">
        <thead>
            <tr>
                <th style="width: 8%;">Niveau</th>
                <th style="width: 28%;">Approbateur</th>
                <th style="width: 20%;">Date</th>
                <th>Commentaires</th>
            </tr>
        </thead>
        <tbody>
            @forelse($document->approvals->where('status', 'approved')->sortBy('level') as $approval)
            <tr>
                <td style="text-align: center;">{{ $approval->level }}</td>
                <td>{{ $approval->approver?->full_name ?? '—' }}</td>
                <td>{{ $approval->approved_at ? $approval->approved_at->timezone(config('app.timezone'))->format('d/m/Y H:i') : '—' }}</td>
                <td>{{ $approval->comments ?? '—' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #888;">Aucune validation enregistrée</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td>
                <div class="sig-line">Le Directeur Général</div>
            </td>
            <td>
                <div class="sig-line">Validé par</div>
            </td>
        </tr>
    </table>

    <p class="footer">Document généré par {{ config('app.name', 'LocaGed') }} — {{ now()->timezone(config('app.timezone'))->format('d/m/Y à H:i') }}</p>

</body>
</html>
