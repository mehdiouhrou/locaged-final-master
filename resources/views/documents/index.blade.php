@extends('layouts.app')

@section('content')

    @php
        $totalDocuments = \App\Models\Document::count();
        $pendingDocuments = \App\Models\Document::where('status', 'pending')->count();
        $approvedDocuments = \App\Models\Document::where('status', 'approved')->count();
        $todayDocuments = \App\Models\Document::whereDate('created_at', today())->count();
        $auditSubtitle = number_format($totalDocuments) . ' · ' . number_format($pendingDocuments) . ' ' . ui_t('pages.stats.pending') . ' · ' . number_format($approvedDocuments) . ' ' . ui_t('pages.stats.approved');
    @endphp

    <div class="activity-log px-4 px-md-0 position-relative">
        <x-page-hero class="mt-4" :title="ui_t('pages.file_audit')" :subtitle="$auditSubtitle" dense>
            <x-slot:actions>
                <div class="btn-group2">
                    <button type="button" class="me-2 me-md-3">
                        <a href="{{ route('users.logs') }}" class="text-decoration-none">{{ ui_t('pages.activity_log.activity_log') }}</a>
                    </button>
                    <button type="button" class="me-2 me-md-3 button-active2">
                        <a href="{{ route('documents.index') }}" class="text-decoration-none">{{ ui_t('pages.file_audit') }}</a>
                    </button>
                    @unless(auth()->user()->can('filter audit logs by assigned services') || auth()->user()->can('view subdepartment scoped documents'))
                        <button type="button" class="me-0">
                            <a href="{{ route('logs.deletions') }}" class="text-decoration-none">{{ __('Deletion log') }}</a>
                        </button>
                    @endunless
                </div>
            </x-slot:actions>
        </x-page-hero>

        <!-- Statistics Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.total_documents') }}</h6>
                                <h3 class="mb-0">{{ number_format($totalDocuments) }}</h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-file text-primary fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.pending') }}</h6>
                                <h3 class="mb-0">{{ number_format($pendingDocuments) }}</h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-clock text-warning fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.approved') }}</h6>
                                <h3 class="mb-0">{{ number_format($approvedDocuments) }}</h3>
                            </div>
                            <div class="bg-success bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-check-circle text-success fa-lg"></i>
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
                                <h6 class="text-muted mb-2 small">{{ ui_t('pages.stats.todays_uploads') }}</h6>
                                <h3 class="mb-0">{{ number_format($todayDocuments) }}</h3>
                            </div>
                            <div class="bg-info bg-opacity-10 rounded-circle p-3">
                                <i class="fas fa-calendar-day text-info fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <livewire:documents-table/>



@endsection
