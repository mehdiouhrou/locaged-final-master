@php
    $notifVersionId = $notification->data['documentLatestVersionId'] ?? null;
    $notifTargetUrl = $notifVersionId
        ? route('document-versions.preview', ['id' => $notifVersionId])
        : null;
    $type = $notification->data['type'] ?? 'info';
    $iconBg = match($type) {
        'success' => 'bg-success-subtle text-success',
        'danger' => 'bg-danger-subtle text-danger',
        'warning' => 'bg-warning-subtle text-warning',
        default => 'bg-info-subtle text-info',
    };
    $iconClass = match($type) {
        'success' => 'fa-check',
        'danger' => 'fa-triangle-exclamation',
        'warning' => 'fa-clock',
        default => 'fa-bell',
    };
    $isUnread = is_null($notification->read_at);
@endphp
<div class="list-group-item py-2 {{ $isUnread ? 'bg-light-subtle' : '' }} @if($notifTargetUrl) notification-item-clickable @endif" wire:key="{{ $notification->id }}" @if($notifTargetUrl) wire:click="markAsReadAndRedirect('{{ $notification->id }}', '{{ $notifTargetUrl }}')" style="cursor: pointer;" @endif>
    <div class="d-flex align-items-start gap-2">
        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $iconBg }}" style="width: 36px; height: 36px;">
            <i class="fa-solid {{ $iconClass }}"></i>
        </div>

        <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2">
                <span class="fw-semibold text-break">{{ $notification->data['title'] ?? ui_t('pages.notifications.notification') }}</span>
                @if($isUnread)
                    <span class="badge rounded-pill bg-danger flex-shrink-0" style="font-size: 0.6rem;">{{ ui_t('pages.notifications.unread') }}</span>
                @endif
            </div>
            <div class="text-muted small mt-1 line-clamp-2">
                {{ $notification->data['body'] ?? '' }}
            </div>
            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
        </div>

        <div class="ms-2 flex-shrink-0" onclick="event.stopPropagation()">
            <button wire:click="deleteNotification('{{ $notification->id }}')" class="btn btn-sm btn-link text-danger p-0" title="{{ ui_t('actions.delete') }}" aria-label="{{ ui_t('actions.delete') }} {{ ui_t('pages.notifications.notification') }}">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
</div>
