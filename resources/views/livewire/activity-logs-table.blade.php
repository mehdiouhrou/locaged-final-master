<div>
    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.total_logs') }}</h6>
                            <h3 class="mb-0">{{ number_format($totalLogs) }}</h3>
                        </div>
                        <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-list text-primary fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.todays_logs') }}</h6>
                            <h3 class="mb-0">{{ number_format($todayLogs) }}</h3>
                        </div>
                        <div class="bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-day text-success fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.this_week') }}</h6>
                            <h3 class="mb-0">{{ number_format($thisWeekLogs) }}</h3>
                        </div>
                        <div class="bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-calendar-week text-info fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2 small">{{ ui_t('pages.activity.cards.active_users') }}</h6>
                            <h3 class="mb-0">{{ number_format($uniqueUsers) }}</h3>
                        </div>
                        <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fas fa-users text-warning fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4 border-bottom-0">
        <li class="nav-item">
            <a class="nav-link {{ $logType === 'documents' ? 'active fw-bold border-bottom-0' : '' }}" 
               href="#" wire:click.prevent="setLogType('documents')"
               style="{{ $logType === 'documents' ? 'border-top: 3px solid var(--bs-primary);' : 'color: var(--bs-secondary);' }}">
                <i class="fas fa-file-alt me-2"></i>{{ ui_t('pages.activity.tabs.document_activity') }}
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $logType === 'authentication' ? 'active fw-bold border-bottom-0' : '' }}" 
               href="#" wire:click.prevent="setLogType('authentication')"
               style="{{ $logType === 'authentication' ? 'border-top: 3px solid var(--bs-primary);' : 'color: var(--bs-secondary);' }}">
                <i class="fas fa-user-shield me-2"></i>{{ ui_t('pages.activity.tabs.authentication_activity') }}
            </a>
        </li>
    </ul>

    <!-- Filters Section -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="search-files">
                        <i class="fas fa-search"></i>
                        <input type="text" placeholder="{{ ui_t('pages.activity.filters.search_placeholder') }}" wire:model.live.debounce.300ms="search" />
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.date_from') }}</label>
                    <input type="date" class="form-control" wire:model.change="dateFrom" />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.date_to') }}</label>
                    <input type="date" class="form-control" wire:model.change="dateTo" />
                </div>
                <div class="col-md-2">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.user') }}</label>
                    <select class="form-select" wire:model.change="userId">
                        <option value="">{{ ui_t('pages.activity.filters.all_users') }}</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                
                @if($logType === 'documents')
                    <div class="col-md-2">
                        <label class="form-label small">{{ ui_t('pages.activity.filters.department') }}</label>
                        <select class="form-select" wire:model.change="departmentId">
                            <option value="">{{ ui_t('pages.activity.filters.all_departments') }}</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}">{{ $department->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label small">{{ ui_t('pages.activity.filters.action_type') }}</label>
                    <select class="form-select" wire:model.change="actionType">
                        <option value="">{{ ui_t('pages.activity.filters.all_actions') }}</option>
                        @if($logType === 'documents')
                            <option value="created">{{ ui_t('pages.activity.actions.created') }}</option>
                            <option value="metadata_updated">{{ ui_t('pages.activity.actions.metadata_updated') }}</option>
                            <option value="approved">{{ ui_t('pages.activity.actions.approved') }}</option>
                            <option value="declined">{{ ui_t('pages.activity.actions.declined') }}</option>
                            <option value="archived">{{ ui_t('pages.activity.actions.archived') }}</option>
                            <option value="permanently_deleted">{{ ui_t('pages.activity.actions.permanently_deleted') }}</option>
                            <option value="download">{{ ui_t('pages.activity.actions.download') }}</option>
                            <option value="viewed">{{ ui_t('pages.activity.actions.viewed') }}</option>
                            <option value="renamed">{{ ui_t('pages.activity.actions.renamed') }}</option>
                            <option value="moved">{{ ui_t('pages.activity.actions.moved') }}</option>
                            <option value="borrowed">{{ ui_t('pages.activity.actions.borrowed') }}</option>
                            <option value="returned">{{ ui_t('pages.activity.actions.returned') }}</option>
                            <option value="expiration_postponed">{{ ui_t('pages.activity.actions.expiration_postponed') }}</option>
                            <option value="category_created">{{ ui_t('pages.activity.actions.category_created') }}</option>
                            <option value="category_updated">{{ ui_t('pages.activity.actions.category_updated') }}</option>
                            <option value="category_deleted">{{ ui_t('pages.activity.actions.category_deleted') }}</option>
                            <option value="document_submitted_for_review">{{ ui_t('pages.activity.actions.document_submitted_for_review') }}</option>
                            <option value="reviewer_assigned">{{ ui_t('pages.activity.actions.reviewer_assigned') }}</option>
                            <option value="reviewer_validated">{{ ui_t('pages.activity.actions.reviewer_validated') }}</option>
                            <option value="sent_back_to_draft">{{ ui_t('pages.activity.actions.sent_back_to_draft') }}</option>
                            <option value="document_resubmitted">{{ ui_t('pages.activity.actions.document_resubmitted') }}</option>
                            <option value="archiving_confirmed">{{ ui_t('pages.activity.actions.archiving_confirmed') }}</option>
                            <option value="export_requested">{{ ui_t('pages.activity.actions.export_requested') }}</option>
                            <option value="export_downloaded">{{ ui_t('pages.activity.actions.export_downloaded') }}</option>
                        @else
                            <option value="login_success">{{ __('Login success') }}</option>
                            <option value="login_failed">{{ __('Login failed') }}</option>
                            <option value="logout">{{ __('Logout') }}</option>
                            <option value="account_locked">{{ __('Account locked') }}</option>
                        @endif
                    </select>
                </div>
                <div class="col-md-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <button type="button" wire:click="resetFilters" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-redo me-1"></i> {{ ui_t('pages.activity.reset_filters') }}
                            </button>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <label class="form-label small mb-0">{{ ui_t('pages.activity.filters.per_page') }}</label>
                            <select class="form-select form-select-sm" wire:model.change="perPage" style="width: auto;">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <div class="btn-group">
                                <button type="button" wire:click="export" class="btn btn-sm btn-success">
                                    <i class="fas fa-file-excel me-1"></i> {{ ui_t('pages.activity.filters.export') }}
                                </button>
                                <a href="{{ route('users.logs.export-pdf') }}?{{ http_build_query(array_filter(['search' => $search, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'userId' => $userId, 'departmentId' => $departmentId, 'actionType' => $actionType, 'logType' => $logType])) }}" class="btn btn-sm btn-danger">
                                    <i class="fas fa-file-pdf me-1"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Section -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.date_time') }}</th>
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.user') }}</th>
                            @if($logType === 'documents')
                                <th style="width: 120px;">{{ ui_t('pages.activity.table.action') }}</th>
                                <th>{{ ui_t('pages.activity.table.document') }}</th>
                            @else
                                <th>{{ __('Email') }}</th>
                                <th style="width: 120px;">{{ __('Type') }}</th>
                            @endif
                            <th style="width: 150px;">{{ ui_t('pages.activity.table.ip_device') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>
                                    <div class="small">
                                        <div class="fw-semibold">{{ $log->occurred_at?->format('Y-m-d') }}</div>
                                        <div class="text-muted">{{ $log->occurred_at?->format('H:i:s') }}</div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @php
                                            $userImageUrl = $log->user?->avatar_url ?? asset('assets/user.png');
                                        @endphp
                                        <div class="flex-shrink-0">
                                            <img
                                                src="{{ $userImageUrl }}"
                                                alt="{{ $log->user?->full_name ?? 'User' }}"
                                                class="rounded-circle"
                                                style="width: 32px; height: 32px; object-fit: cover;"
                                                onerror="this.onerror=null;this.src='{{ asset('assets/user.png') }}';"
                                            />
                                        </div>
                                        <div class="flex-grow-1 ms-2">
                                            <div class="fw-semibold small">{{ $log->user?->full_name ?? ($log->user_name ?? ui_t('pages.activity.table.na')) }}</div>
                                            @if($log->user?->departments->first())
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $log->user->departments->first()->name }}</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                
                                @if($logType === 'documents')
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
                                    <td>
                                        @if($log->document)
                                            @php
                                                // Check if document is permanently deleted (trashed)
                                                $isTrashed = $log->document->trashed();
                                                $linkUrl = $isTrashed 
                                                    ? route('logs.deletions', ['document_id' => $log->document->id])
                                                    : route('documents.all', ['document_id' => $log->document->id]);
                                            @endphp
                                            <a href="{{ $linkUrl }}" class="text-decoration-none">
                                                <div class="fw-semibold text-primary">{{ \Str::limit($log->document->title, 40) }}</div>
                                            </a>
                                            @if($log->document->department)
                                                <div class="text-muted small">{{ $log->document->department->name }}</div>
                                            @endif
                                            @if($log->action === 'expiration_postponed')
                                                @php $meta = $log->metadata ?? []; @endphp
                                                <div class="small mt-1">
                                                    @if(!empty($meta['amount']) && !empty($meta['unit']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <span class="fw-semibold">{{ ui_t('pages.destructions.postpone.time_unit') }}</span> :
                                                            {{ $meta['amount'] }} {{ ui_t('pages.destructions.postpone.' . $meta['unit']) }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($meta['previous_expire_at']) && !empty($meta['new_expire_at']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <span class="text-danger">{{ \Carbon\Carbon::parse($meta['previous_expire_at'])->format('d/m/Y') }}</span>
                                                            <i class="fas fa-arrow-right mx-1" style="font-size: 0.6rem;"></i>
                                                            <span class="text-success">{{ \Carbon\Carbon::parse($meta['new_expire_at'])->format('d/m/Y') }}</span>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($log->action === 'borrowed')
                                                @php $meta = $log->metadata ?? []; @endphp
                                                <div class="small mt-1">
                                                    @if(!empty($meta['borrower_name']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <span class="fw-semibold">{{ ui_t('pages.documents.move.borrower_name') }}</span> : {{ $meta['borrower_name'] }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($meta['borrowed_at']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            {{ ui_t('pages.activity.actions.borrowed') }} : {{ \Carbon\Carbon::parse($meta['borrowed_at'])->format('d/m/Y H:i') }}
                                                        </div>
                                                    @endif
                                                    @if(!empty($meta['due_at']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            {{ ui_t('pages.documents.move.due_at') }} : {{ \Carbon\Carbon::parse($meta['due_at'])->format('d/m/Y') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                            @if($log->action === 'returned')
                                                @php $meta = $log->metadata ?? []; @endphp
                                                <div class="small mt-1">
                                                    @if(!empty($meta['returned_at']))
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            {{ ui_t('pages.activity.actions.returned') }} : {{ \Carbon\Carbon::parse($meta['returned_at'])->format('d/m/Y H:i') }}
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        @elseif(in_array($log->action, ['category_created', 'category_updated', 'category_deleted'], true))
                                            @php
                                                $meta = $log->metadata ?? [];
                                                $catLabel = $meta['name']
                                                    ?? ($meta['after']['name'] ?? null)
                                                    ?? ($meta['before']['name'] ?? null)
                                                    ?? ('#' . ($meta['category_id'] ?? ''));
                                            @endphp
                                            <div class="fw-semibold">{{ \Str::limit((string) $catLabel, 60) }}</div>
                                            <div class="text-muted small">{{ __('pages.upload.category') }}</div>
                                            @if($log->action === 'category_updated' && !empty($meta['changes']))
                                                <div class="small mt-1">
                                                    @foreach($meta['changes'] as $field => $change)
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <span class="fw-semibold">{{ ui_t('pages.category_fields.' . $field) }}</span> :
                                                            <span class="text-danger">{{ $change['old'] ?? '—' }}</span>
                                                            <i class="fas fa-arrow-right mx-1" style="font-size: 0.6rem;"></i>
                                                            <span class="text-success">{{ $change['new'] ?? '—' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        @elseif(in_array($log->action, ['box_created', 'box_moved', 'box_deleted'], true))
                                            @php $meta = $log->metadata ?? []; @endphp
                                            <div class="fw-semibold">{{ $meta['box_number'] ?? $meta['box_number_new'] ?? ('#' . ($meta['box_id'] ?? '')) }}</div>
                                            <div class="text-muted small">{{ ui_t('pages.physical_locations.box') }}</div>
                                            @if($log->action === 'box_moved')
                                                <div class="small mt-1">
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <span class="text-danger">{{ $meta['location_old'] ?? '—' }}</span>
                                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.6rem;"></i>
                                                        <span class="text-success">{{ $meta['location_new'] ?? '—' }}</span>
                                                    </div>
                                                </div>
                                            @elseif(!empty($meta['location']))
                                                <div class="text-muted" style="font-size: 0.75rem;">{{ $meta['location'] }}</div>
                                            @endif
                                        @elseif(in_array($log->action, ['user_created', 'user_updated', 'user_deleted', 'user_role_changed'], true))
                                            @php
                                                $meta = $log->metadata ?? [];
                                                $userLabel = $log->user_name ?? ($meta['full_name'] ?? ('#' . ($meta['user_id'] ?? '')));
                                            @endphp
                                            <div class="fw-semibold">{{ \Str::limit((string) $userLabel, 60) }}</div>
                                            <div class="text-muted small">{{ ui_t('pages.user_fields.full_name') }}</div>
                                            @if($log->action === 'user_updated' && !empty($meta['changes']))
                                                <div class="small mt-1">
                                                    @foreach($meta['changes'] as $field => $change)
                                                        <div class="text-muted" style="font-size: 0.75rem;">
                                                            <span class="fw-semibold">{{ ui_t('pages.user_fields.' . $field) }}</span> :
                                                            <span class="text-danger">{{ $change['old'] ?? '—' }}</span>
                                                            <i class="fas fa-arrow-right mx-1" style="font-size: 0.6rem;"></i>
                                                            <span class="text-success">{{ $change['new'] ?? '—' }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                            @if($log->action === 'user_role_changed')
                                                <div class="small mt-1">
                                                    <div class="text-muted" style="font-size: 0.75rem;">
                                                        <span class="text-danger">{{ $meta['previous_role'] ?? '—' }}</span>
                                                        <i class="fas fa-arrow-right mx-1" style="font-size: 0.6rem;"></i>
                                                        <span class="text-success">{{ $meta['new_role'] ?? '—' }}</span>
                                                    </div>
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-muted">{{ ui_t('pages.activity.table.na') }}</span>
                                        @endif
                                    </td>
                                @else
                                    <td>
                                        <div class="small">{{ $log->email }}</div>
                                    </td>
                                    <td>
                                        <span class="badge 
                                            @if($log->type === 'login_success') bg-success-subtle text-success
                                            @elseif($log->type === 'login_failed') bg-danger-subtle text-danger
                                            @elseif($log->type === 'logout') bg-secondary-subtle text-secondary
                                            @elseif($log->type === 'disconnection') bg-secondary-subtle text-secondary
                                            @else bg-secondary-subtle text-secondary
                                            @endif rounded-pill px-2 py-1">
                                            @php
                                                $typeTranslations = [
                                                    'login_success' => __('Login success'),
                                                    'login_failed' => __('Login failed'),
                                                    'logout' => __('Logout'),
                                                    'disconnection' => __('Disconnection'),
                                                ];
                                            @endphp
                                            {{ $typeTranslations[$log->type] ?? __(ucfirst(str_replace('_', ' ', $log->type))) }}
                                        </span>
                                    </td>
                                @endif

                                <td>
                                    <div class="small">
                                        <div class="fw-semibold">{{ $log->ip_address ?? ui_t('pages.activity.table.na') }}</div>
                                        <div class="text-muted" style="font-size: 0.7rem;">
                                            <i class="fas fa-network-wired me-1"></i>{{ \Str::limit($log->user_agent ?? 'Device Info', 20) }}
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $logType === 'documents' ? 5 : 5 }}" class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <div class="text-muted">{{ ui_t('pages.activity.empty') }}</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white border-top">
            <x-pagination :items="$logs"></x-pagination>
        </div>
    </div>
</div>

