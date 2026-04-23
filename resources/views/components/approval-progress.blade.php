@php
    $workflowService = app(\App\Services\WorkflowApprovalService::class);
    $currentLevel = $workflowService->getCurrentLevel($document);
    $progressLabel = $workflowService->getProgressLabel($document);
    $approvals = $document->approvals()->with(['approver', 'workflowRule'])->orderBy('level')->get();
@endphp

@if($document->status === 'pending' && $approvals->isNotEmpty())
    <div class="card border-0 shadow-sm mb-4 bg-light">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0"><i class="fa-solid fa-stairs me-2"></i> Progression du Workflow</h6>
                <span class="badge bg-primary">{{ $progressLabel }}</span>
            </div>

            <div class="workflow-steps position-relative">
                @foreach($approvals as $index => $approval)
                    <div class="workflow-step d-flex align-items-start mb-3 {{ !$loop->last ? 'pb-3 border-start ms-3 ps-4 position-relative' : 'ms-3 ps-4' }}">
                        <div class="step-icon position-absolute start-0 translate-middle-x rounded-circle d-flex align-items-center justify-content-center" 
                             style="width: 30px; height: 30px; background-color: {{ $approval->status === 'approved' ? '#198754' : ($approval->status === 'pending' ? '#ffc107' : '#f8f9fa') }}; color: {{ $approval->status === 'approved' ? 'white' : 'black' }}; top: 0;">
                            @if($approval->status === 'approved')
                                <i class="fa-solid fa-check fa-xs"></i>
                            @elseif($approval->status === 'pending')
                                <i class="fa-solid fa-clock fa-xs"></i>
                            @else
                                <i class="fa-solid fa-circle fa-xs text-muted"></i>
                            @endif
                        </div>
                        <div class="step-content">
                            <div class="d-flex justify-content-between">
                                <strong class="d-block">Niveau {{ $approval->level }}</strong>
                                @if($approval->approved_at)
                                    <small class="text-muted">{{ $approval->approved_at->format('d/m/Y H:i') }}</small>
                                @endif
                            </div>
                            <span class="text-muted small">
                                @if($approval->workflowRule->approver_user_id)
                                    Individuel : {{ $approval->workflowRule->approverUser->full_name }}
                                @else
                                    Rôle : {{ $approval->workflowRule->approver_role }}
                                @endif
                            </span>
                            @if($approval->status === 'approved')
                                <div class="mt-1 small text-success">
                                    <i class="fa-solid fa-user-check me-1"></i> Approuvé par {{ $approval->approver->full_name }}
                                </div>
                                @if($approval->comments)
                                    <div class="mt-1 p-2 bg-white rounded border-start border-success border-3 small italic">
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
