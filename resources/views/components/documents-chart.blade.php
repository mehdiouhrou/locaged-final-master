<div class="card border-0 categories-section p-4 h-100 d-flex flex-column">
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3 mb-4 flex-wrap">
        <div class="min-w-0">
            <p class="text-muted small fw-bold text-uppercase mb-2" style="letter-spacing: 0.06em;">{{ ui_t('pages.chart.statistics') }}</p>
            <h5 class="fw-bold mb-0">{{ ui_t('pages.chart.summary_approvals') }}</h5>
        </div>
        <div class="d-inline-flex align-items-center flex-wrap gap-1" id="chartFilters" role="group" aria-label="{{ ui_t('pages.chart.statistics') }}">
            <button type="button" class="btn btn-sm px-3 py-2 border-0 button-timeframe" data-period="weekly">{{ ui_t('pages.chart.weekly') }}</button>
            <button type="button" class="btn btn-sm px-3 py-2 border-0 button-timeframe button-active" data-period="monthly">{{ ui_t('pages.chart.monthly') }}</button>
            <button type="button" class="btn btn-sm px-3 py-2 border-0 button-timeframe" data-period="yearly">{{ ui_t('pages.chart.yearly') }}</button>
        </div>
    </div>

    <div class="row flex-grow-1 h-100 min-h-0">
        <div class="col-md-9" style="overflow-x: visible;">
            <div style="height: 100%; overflow-x: visible;">
                <canvas
                    id="approvalChart"
                    data-weekly='@json($weeklyData)'
                    data-monthly='@json($monthlyData)'
                    data-yearly='@json($yearlyData)'
                    data-approved-label="{{ ui_t('pages.chart.approved') }}"
                    data-pending-label="{{ ui_t('pages.chart.pending') }}"
                    data-expired-label="{{ ui_t('pages.chart.expired') }}"
                    @php
                        $dayNames = [
                            'sun' => ui_t('pages.chart.days.sun'),
                            'mon' => ui_t('pages.chart.days.mon'),
                            'tue' => ui_t('pages.chart.days.tue'),
                            'wed' => ui_t('pages.chart.days.wed'),
                            'thu' => ui_t('pages.chart.days.thu'),
                            'fri' => ui_t('pages.chart.days.fri'),
                            'sat' => ui_t('pages.chart.days.sat'),
                        ];
                    @endphp
                    data-days='@json($dayNames)'
                ></canvas>

            </div>
        </div>
        <div class="col-md-3 d-flex align-items-center ps-3">
            <div class="border-chart ps-2 d-flex align-items-center">
                <div class="">
                    <div
                        class="border border-black rounded-pill px-3 py-2 d-inline-block mb-2"
                    >
                        <i
                            class="bi bi-circle-fill text-secondary me-1"
                            style="font-size: 0.6rem"
                        ></i>
                        <span
                            class="bg-black rounded-circle d-inline-block"
                            style="width: 12px; height: 12px"
                        ></span>
                        {{ ui_t('pages.chart.all_status') }}
                    </div>
                    <ul class="list-unstyled mt-3">
                        <li
                            class="d-flex justify-content-between align-items-center mb-2"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="rounded-circle d-inline-block"
                                    style="width: 10px; height: 10px; background-color: #c1121f"
                                ></span>
                                <span>{{ ui_t('pages.chart.approved') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['approved'] }}</span>
                        </li>
                        <li
                            class="d-flex justify-content-between align-items-center mb-2"
                        >
                            <div class="d-flex align-items-center gap-2">
                                <span
                                    class="rounded-circle d-inline-block"
                                    style="width: 10px; height: 10px; background-color: #f59e0b"
                                ></span>
                                <span>{{ ui_t('pages.chart.pending') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['pending'] }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle d-inline-block" style="width: 10px; height: 10px; background-color: #64748b"></span>
                                <span>{{ ui_t('pages.chart.expired') }}</span>
                            </div>
                            <span class="fw-semibold">{{ $statusSummary['expired'] ?? 0 }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
