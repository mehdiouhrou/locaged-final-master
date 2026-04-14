@props(['document'])

@php
    $histories = $document->statusHistories ?? collect();
    $tz = config('app.timezone');
@endphp

<div class="lgv2-approval-timeline">
    <div class="lgv2-approval-timeline__row lgv2-approval-timeline__row--done">
        <div class="lgv2-approval-timeline__dot" aria-hidden="true"></div>
        <div class="lgv2-approval-timeline__body">
            <div class="lgv2-approval-timeline__label">{{ ui_t('pages.document_detail.submitted') }}</div>
            <div class="lgv2-approval-timeline__detail text-muted">{{ $document->createdBy?->full_name ?? '—' }}</div>
            <div class="lgv2-approval-timeline__date small text-muted">{{ $document->created_at?->timezone($tz)->format('d/m/Y H:i') }}</div>
        </div>
    </div>

    @forelse($histories as $h)
        @php
            $isLast = $loop->last;
            $st = is_string($document->status) ? $document->status : ($document->status?->value ?? '');
            $rowState = ($isLast && $st === 'pending') ? 'current' : 'done';
        @endphp
        <div class="lgv2-approval-timeline__row lgv2-approval-timeline__row--{{ $rowState }}">
            <div class="lgv2-approval-timeline__dot" aria-hidden="true"></div>
            <div class="lgv2-approval-timeline__body">
                <div class="lgv2-approval-timeline__label">{{ ui_t('pages.document_detail.status_change') }}</div>
                <div class="lgv2-approval-timeline__detail text-muted">
                    {{ ui_t('pages.documents.status.' . ($h->from_status ?? '')) }}
                    <i class="fa-solid fa-arrow-right mx-1 small text-muted" aria-hidden="true"></i>
                    {{ ui_t('pages.documents.status.' . ($h->to_status ?? '')) }}
                    @if($h->changedBy)
                        <span class="d-block mt-1">{{ $h->changedBy->full_name }}</span>
                    @endif
                </div>
                <div class="lgv2-approval-timeline__date small text-muted">{{ $h->changed_at?->timezone($tz)->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    @empty
        @php $curSt = is_string($document->status) ? $document->status : ($document->status?->value ?? ''); @endphp
        <div class="lgv2-approval-timeline__row lgv2-approval-timeline__row--{{ $curSt === 'pending' ? 'current' : 'done' }}">
            <div class="lgv2-approval-timeline__dot" aria-hidden="true"></div>
            <div class="lgv2-approval-timeline__body">
                <div class="lgv2-approval-timeline__label">{{ ui_t('tables.status') }}</div>
                <div class="lgv2-approval-timeline__detail text-muted">{{ ui_t('pages.documents.status.' . $curSt) }}</div>
            </div>
        </div>
    @endforelse
</div>
