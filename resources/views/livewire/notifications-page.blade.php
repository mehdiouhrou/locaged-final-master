<div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold mb-0">{{ ui_t('pages.notifications.title') }}</h4>
        @if($notifications->total() > 0)
            <button type="button" wire:click="markAllAsRead" class="btn btn-sm btn-outline-secondary">
                <i class="fa-solid fa-check-double me-1"></i>{{ ui_t('pages.notifications.mark_all_as_read') }}
            </button>
        @endif
    </div>

    @if($notifications->isEmpty())
        <div class="text-center text-muted py-5">
            <i class="fa-regular fa-bell-slash fa-2x mb-3 d-block"></i>
            {{ ui_t('pages.notifications.empty') }}
        </div>
    @else
        <div class="d-flex flex-column gap-2">
            @foreach($notifications as $notification)
                @php
                    $type = $notification->data['type'] ?? 'info';
                    $borderClass = match($type) {
                        'success' => 'border-start border-4 border-success',
                        'danger' => 'border-start border-4 border-danger',
                        'warning' => 'border-start border-4 border-warning',
                        default => 'border-start border-4 border-info',
                    };
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
                    $versionId = $notification->data['documentLatestVersionId'] ?? null;
                    $targetUrl = $versionId ? route('document-versions.preview', ['id' => $versionId]) : null;
                    $isUnread = is_null($notification->read_at);
                @endphp
                <div class="card {{ $borderClass }} {{ $isUnread ? 'bg-light-subtle' : '' }} shadow-sm"
                     @if($targetUrl) wire:click="markAsReadAndRedirect('{{ $notification->id }}', '{{ $targetUrl }}')" style="cursor: pointer;" @endif>
                    <div class="card-body py-3 d-flex align-items-start gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 {{ $iconBg }}" style="width: 40px; height: 40px;">
                            <i class="fa-solid {{ $iconClass }}"></i>
                        </div>

                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-semibold">{{ $notification->data['title'] ?? ui_t('pages.notifications.notification') }}</span>
                                @if($isUnread)
                                    <span class="badge rounded-pill bg-primary" style="font-size: 0.6rem;">{{ ui_t('pages.notifications.unread') }}</span>
                                @endif
                            </div>
                            <div class="text-muted small mt-1">{{ $notification->data['body'] ?? '' }}</div>
                            <div class="text-muted small mt-1">{{ $notification->created_at->diffForHumans() }}</div>
                        </div>

                        <div class="d-flex align-items-center gap-2 flex-shrink-0" onclick="event.stopPropagation()">
                            @if($isUnread)
                                <button type="button" wire:click="markAsRead('{{ $notification->id }}')" class="btn btn-sm btn-link text-secondary p-0" title="{{ ui_t('pages.notifications.marked_as_read') }}">
                                    <i class="fa-solid fa-check"></i>
                                </button>
                            @endif
                            <button type="button" wire:click="deleteNotification('{{ $notification->id }}')" class="btn btn-sm btn-link text-danger p-0" title="{{ ui_t('actions.delete') }}">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-4">
            <x-pagination :items="$notifications" />
        </div>
    @endif
</div>
