@extends('layouts.app')

@section('content')
    <div class="mt-5">
        <div class="d-flex justify-content-between mb-5">
            <div class="d-flex align-items-center all-cat">
                <a href="{{ route('users.index') }}">
                    <h4 class="me-3">{{ ui_t('pages.users_page.users') }} <i class="fa-solid fa-angle-right"></i></h4>
                </a>
                <h5 class="me-3">{{ $user->full_name }}</h5>
            </div>
            @can('view', $user)
                <a href="{{ route('users.show', ['user' => $user->id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa-solid fa-circle-user me-1"></i>{{ ui_t('pages.user_activity.profile') }}
                </a>
            @endcan
        </div>

        <div class="recent-files-section">
            <div class="d-flex align-items-center mb-4">
                <img
                    src="{{ $user->avatar_url ?? asset('assets/user.png') }}"
                    alt="{{ $user->full_name }}"
                    class="rounded-circle me-3"
                    style="width: 48px; height: 48px; object-fit: cover;"
                    onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                />
                <div>
                    <div class="fw-bold fs-5">{{ $user->full_name }}</div>
                    <div class="text-muted small">{{ $user->role }}</div>
                </div>
            </div>

            @if($auditLogs->isEmpty())
                <p class="text-muted mb-0">{{ ui_t('pages.user_activity.no_activity') ?? __('Aucune activité pour le moment.') }}</p>
            @else
                <div class="files-table-container">
                    <table class="files-table table align-middle mb-0">
                        <thead>
                            <tr>
                                <th>{{ ui_t('pages.activity.table.date_time') }}</th>
                                <th>{{ ui_t('pages.activity.table.action') }}</th>
                                <th>{{ ui_t('pages.activity.table.document') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($auditLogs as $log)
                                <tr>
                                    <td>
                                        <div class="small">
                                            <div class="fw-semibold">{{ $log->occurred_at?->format('Y-m-d') }}</div>
                                            <div class="text-muted">{{ $log->occurred_at?->format('H:i:s') }}</div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge
                                            @if(in_array($log->action, ['created', 'approved', 'updated', 'downloaded', 'viewed', 'renamed', 'unlocked', 'moved', 'viewed_ocr', 'category_created', 'category_updated'])) bg-success-subtle text-success
                                            @elseif(in_array($log->action, ['declined', 'failed_access'])) bg-danger-subtle text-danger
                                            @elseif(in_array($log->action, ['permanently_deleted', 'destroyed', 'deleted', 'category_deleted'])) bg-dark-subtle text-dark
                                            @elseif(in_array($log->action, ['archived', 'locked'])) bg-warning-subtle text-warning
                                            @else bg-secondary-subtle text-secondary
                                            @endif rounded-pill px-2 py-1">
                                            {{ ui_t('pages.activity.actions.' . $log->action) ?? ucfirst(str_replace('_', ' ', $log->action)) }}
                                        </span>
                                    </td>
                                    <td>{{ $log->document?->title ?? ui_t('pages.user_activity.document_unavailable') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-3">
                    <x-pagination :items="$auditLogs" />
                </div>
            @endif
        </div>
    </div>
@endsection
