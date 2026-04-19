@extends('layouts.app')

@section('content')
    <div class="activity-log px-4 px-md-0 position-relative">
        <div class="d-md-flex mt-5 mb-4 justify-content-between align-items-center">
            <h4 class="mb-4">{{ __('pages.destructions.page_heading') }}</h4>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th scope="col">{{ __('pages.destructions.document_name') }}</th>
                                <th scope="col">{{ __('pages.destructions.created_by') }}</th>
                                <th scope="col">{{ __('pages.destructions.col_deleted_by') }}</th>
                                <th scope="col">{{ __('pages.destructions.col_deleted_at') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                @php
                                    $doc = $log->document; // withTrashed() so we still see the record
                                @endphp
                                <tr>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 260px;">
                                            {{ $doc?->title ?? __('(Document deleted)') }}
                                        </div>
                                        <div class="text-muted small">
                                            {{ __('pages.destructions.document_id_label') }} {{ $log->document_id }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                            {{ $doc?->createdBy?->full_name ?? '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-truncate" style="max-width: 200px;">
                                            {{ $log->user?->full_name ?? $log->user?->email ?? '—' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-semibold">
                                            {{ optional($log->occurred_at)->format('Y-m-d H:i') }}
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        {{ __('pages.destructions.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="p-3">
                    <x-pagination :items="$logs" />
                </div>
            </div>
        </div>
    </div>
@endsection
