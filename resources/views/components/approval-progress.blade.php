@php
    $workflowService = app(\App\Services\WorkflowApprovalService::class);
    $currentLevel = $workflowService->getCurrentLevel($document);
    $progressLabel = $workflowService->getProgressLabel($document);
    // withoutGlobalScopes sur workflowRule : le scope de WorkFlowRule filtre par
    // permission utilisateur et rendrait la règle null pour les non-admins.
    $approvals = $document->approvals()
        ->with([
            'approver',
            'workflowRule' => fn ($q) => $q->withoutGlobalScopes(),
        ])
        ->orderBy('level')
        ->orderBy('id')
        ->get();
@endphp

@if($approvals->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-stairs me-2"></i> Progression du Workflow</h6>
                <span class="badge bg-primary">{{ $progressLabel }}</span>
            </div>

            <div class="workflow-steps position-relative">
                @foreach($approvals as $approval)
                    @php
                        $isCurrentLevel = ($approval->level === $currentLevel && $approval->status === 'pending');
                        $rule = $approval->workflowRule; // déjà chargé sans global scope
                        $bgColor = match($approval->status) {
                            'approved' => '#198754',
                            'declined' => '#dc3545',
                            'pending'  => '#ffc107',
                            default    => '#f8f9fa',
                        };
                        $textColor = in_array($approval->status, ['approved', 'declined']) ? 'white' : 'black';
                    @endphp
                    <div class="workflow-step d-flex align-items-start mb-3 {{ !$loop->last ? 'pb-3 border-start ms-3 ps-4 position-relative' : 'ms-3 ps-4' }}{{ $isCurrentLevel ? ' rounded' : '' }}"
                         @if($isCurrentLevel) style="background: rgba(255,193,7,.08); padding: 6px 8px;" @endif>
                        <div class="step-icon position-absolute start-0 translate-middle-x rounded-circle d-flex align-items-center justify-content-center"
                             style="width:30px;height:30px;background-color:{{ $bgColor }};color:{{ $textColor }};top:0;flex-shrink:0;">
                            @if($approval->status === 'approved')
                                <i class="fa-solid fa-check fa-xs"></i>
                            @elseif($approval->status === 'declined')
                                <i class="fa-solid fa-xmark fa-xs"></i>
                            @elseif($approval->status === 'pending')
                                <i class="fa-solid fa-clock fa-xs"></i>
                            @else
                                <i class="fa-solid fa-circle fa-xs text-muted"></i>
                            @endif
                        </div>

                        <div class="step-content w-100">
                            <div class="d-flex justify-content-between align-items-center">
                                <strong class="d-block">
                                    Niveau {{ $approval->level }}
                                    @if($isCurrentLevel)
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:.65rem;">En cours</span>
                                    @endif
                                </strong>
                                @if($approval->approved_at)
                                    <small class="text-muted">{{ $approval->approved_at->format('d/m/Y H:i') }}</small>
                                @elseif($approval->declined_at)
                                    <small class="text-muted">{{ $approval->declined_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>

                            @if($rule)
                                <span class="text-muted small">
                                    @if($rule->approver_user_id)
                                        <i class="fa-solid fa-user me-1"></i>{{ optional($rule->approverUser)->full_name ?? 'Individuel' }}
                                    @elseif($rule->approver_role)
                                        <i class="fa-solid fa-users me-1"></i>Rôle : {{ $rule->approver_role }}
                                    @endif
                                </span>
                            @endif

                            @if($approval->status === 'approved')
                                <div class="mt-1 small text-success">
                                    <i class="fa-solid fa-user-check me-1"></i>
                                    Approuvé par {{ optional($approval->approver)->full_name ?? '—' }}
                                </div>
                                @if($approval->comments)
                                    <div class="mt-1 p-2 bg-white rounded border-start border-success border-3 small fst-italic">
                                        "{{ $approval->comments }}"
                                    </div>
                                @endif
                            @elseif($approval->status === 'declined')
                                <div class="mt-1 small text-danger">
                                    <i class="fa-solid fa-user-xmark me-1"></i>
                                    Refusé par {{ optional($approval->approver)->full_name ?? '—' }}
                                </div>
                                @if($approval->comments)
                                    <div class="mt-1 p-2 bg-white rounded border-start border-danger border-3 small fst-italic">
                                        "{{ $approval->comments }}"
                                    </div>
                                @endif
                            @elseif($approval->status === 'pending')
                                <div class="mt-1 small text-warning">
                                    <i class="fa-solid fa-hourglass-half me-1"></i> En attente de validation
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif

<style>
    .workflow-step:not(:last-child)::before {
        content: '';
        position: absolute;
        left: -1px;
        top: 30px;
        height: calc(100% - 30px);
        border-left: 2px dashed #dee2e6;
    }
    .workflow-step.border-start {
        border-left: 2px solid #dee2e6 !important;
    }
</style>
