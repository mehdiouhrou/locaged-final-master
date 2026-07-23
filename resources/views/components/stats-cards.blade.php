<div class="stats-grid">
    <a href="{{ route('documents.all', ['show_expired' => 1, 'page_title' => 'all_documents']) }}" class="text-decoration-none text-reset">
    <div class="stat-card red">
        <div class="stat-icon">
            <i class="fa-solid fa-file"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $totalDocuments }}</h3>
                {{--<span class="stat-change"
                >+11.01% <i class="fa-solid fa-arrow-trend-up"></i
                    ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.all_documents') }}</p>
        </div>
    </div>
    </a>

    @if(auth()->user() && auth()->user()->can('view service document') && ! auth()->user()->can('access management sidebar'))
        {{-- Service Users: show pending documents on All Documents page --}}
        <a href="{{ route('documents.all', ['status' => \App\Enums\DocumentStatus::Pending->value, 'page_title' => 'pending_documents', 'show_expired' => 1, 'lock_status' => 1]) }}" class="text-decoration-none text-reset">
    @else
        {{-- Other roles: show pending approvals page --}}
        <a href="{{ route('documents.status', ['show_expired' => 1]) }}" class="text-decoration-none text-reset">
    @endif
    <div class="stat-card blue">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['pending'] }}</h3>
                {{-- <span class="stat-change"
                 >-0.01% <i class="fa-solid fa-arrow-trend-down"></i
                     ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.pending') }}</p>
        </div>
    </div>
    </a>


    <a href="{{ route('documents.active') }}" class="text-decoration-none text-reset">
    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['active_documents'] ?? 0 }}</h3>
            </div>
            <p>Documents actifs</p>
        </div>
    </div>
    </a>


    <a href="{{ route('documents.all', ['status' => 'expired', 'page_title' => 'expired_documents', 'show_expired' => 1, 'hide_status_filter' => 1, 'lock_status' => 1]) }}" class="text-decoration-none text-reset">
    <div class="stat-card yellow">
        <div class="stat-icon">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <div class="stat-content">
            <div class="d-flex justify-content-between">
                <h3>{{ $statusSummary['expired'] ?? 0 }}</h3>
                {{--  <span class="stat-change"
                  >+11.01% <i class="fa-solid fa-arrow-trend-up"></i
                      ></span>--}}
            </div>
            <p>{{ ui_t('pages.stats.expired') }}</p>
        </div>
    </div>
    </a>


</div>
