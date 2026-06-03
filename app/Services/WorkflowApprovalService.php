<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApproval;
use App\Models\WorkFlowRule;
use App\Models\User;
use App\Enums\DocumentStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowApprovalService
{
    /**
     * Initialiser le workflow lors de la création d'un document
     */
    public function initializeWorkflow(Document $document): void
    {
        $query = WorkFlowRule::withoutGlobalScopes()
            ->where('category_id', $document->category_id)
            ->where('level', 1)
            ->where('is_active', true);

        if ($document->amount !== null) {
            $query->where(function ($q) use ($document) {
                $q->whereNull('min_amount')
                  ->orWhere('min_amount', '<=', $document->amount);
            })->where(function ($q) use ($document) {
                $q->whereNull('max_amount')
                  ->orWhere('max_amount', '>', $document->amount);
            });
        }

        $query->orderBy('level', 'asc');

        $rule = $query->first();

        if ($rule) {
            $document->approvals()->create([
                'workflow_rule_id' => $rule->id,
                'level' => 1,
                'status' => 'pending',
            ]);

            // Envoyer notification aux approbateurs du niveau 1
            $this->notifyLevelApprovers($document, 1);
        } else {
            // Si aucune règle n'est définie, on approuve directement.
            // On passe par DB::table pour éviter le hook updating() qui exige auth()->id().
            DB::table('documents')
                ->where('id', $document->id)
                ->update(['status' => DocumentStatus::Approved->value]);
        }
    }

    /**
     * Approuver un niveau spécifique
     */
    public function approveLevel(Document $document, int $level, User $approver, ?string $comments = null): bool
    {
        return DB::transaction(function () use ($document, $level, $approver, $comments) {
            $approval = $document->approvals()
                ->where('level', $level)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return false;
            }

            $approval->update([
                'status' => 'approved',
                'approver_user_id' => $approver->id,
                'approved_at' => now(),
                'comments' => $comments,
            ]);

            // Logger l'action
            $document->logAction("approved_level_$level", null, ['approver' => $approver->full_name]);

            // Si requires_all : s'assurer que tous les approbateurs du niveau ont validé
            // avant de progresser. S'il reste des pending au même niveau, on attend.
            $stillPendingAtLevel = $document->approvals()
                ->where('level', $level)
                ->where('status', 'pending')
                ->exists();

            if ($stillPendingAtLevel) {
                return true; // d'autres approbateurs doivent encore valider
            }

            // Vérifier s'il y a un niveau suivant
            $this->cascadeToNextLevel($document, $level);

            return true;
        });
    }

    /**
     * Refuser un niveau
     */
    public function declineLevel(Document $document, int $level, User $approver, string $reason): bool
    {
        return DB::transaction(function () use ($document, $level, $approver, $reason) {
            $approval = $document->approvals()
                ->where('level', $level)
                ->where('status', 'pending')
                ->first();

            if (!$approval) {
                return false;
            }

            $approval->update([
                'status' => 'declined',
                'approver_user_id' => $approver->id,
                'declined_at' => now(),
                'comments' => $reason,
            ]);

            // Le document est refusé globalement
            $document->update(['status' => DocumentStatus::Declined->value]);

            // Logger l'action
            $document->logAction("declined_level_$level", null, ['reason' => $reason, 'approver' => $approver->full_name]);

            return true;
        });
    }

    /**
     * Passer au niveau suivant ou finaliser.
     * Si requires_all=true sur la règle du niveau suivant, on crée un DocumentApproval
     * par approbateur éligible (multi-signature), et on attend que tous aient validé.
     */
    private function cascadeToNextLevel(Document $document, int $currentLevel): void
    {
        $nextLevel = $currentLevel + 1;

        $query = WorkFlowRule::withoutGlobalScopes()
            ->where('category_id', $document->category_id)
            ->where('level', $nextLevel)
            ->where('is_active', true);

        if ($document->amount !== null) {
            $query->where(function ($q) use ($document) {
                $q->whereNull('min_amount')
                  ->orWhere('min_amount', '<=', $document->amount);
            })->where(function ($q) use ($document) {
                $q->whereNull('max_amount')
                  ->orWhere('max_amount', '>', $document->amount);
            });
        }

        $query->orderBy('level', 'asc');

        $nextRule = $query->first();

        if ($nextRule) {
            if ($nextRule->requires_all) {
                // Double (ou multi) signature : créer un approval par approbateur éligible
                $approvers = $nextRule->getApprovers();
                foreach ($approvers as $approver) {
                    $document->approvals()->create([
                        'workflow_rule_id' => $nextRule->id,
                        'level'            => $nextLevel,
                        'status'           => 'pending',
                        'approver_user_id' => $approver->id,
                    ]);
                }
            } else {
                // Comportement standard : un seul slot pending
                $document->approvals()->create([
                    'workflow_rule_id' => $nextRule->id,
                    'level'            => $nextLevel,
                    'status'           => 'pending',
                ]);
            }

            // Notification niveau suivant
            $this->notifyLevelApprovers($document, $nextLevel);
        } else {
            // C'était le dernier niveau
            $this->finalizeApproval($document);
        }
    }

    /**
     * Marquer le document comme approuvé final
     */
    private function finalizeApproval(Document $document): void
    {
        // On passe par DB::table pour éviter le hook updating() qui exige auth()->id().
        // Le workflow peut être finalisé dans un job/queue sans session active.
        DB::table('documents')
            ->where('id', $document->id)
            ->update(['status' => DocumentStatus::Approved->value]);

        // Recharger le modèle pour que les appels suivants (logAction, Scout, OCR)
        // voient bien le statut approved à jour.
        $document->refresh();

        // Document::booted handles OCR queuing and Typesense indexing on status change
        $document->logAction('approved');

        if (!empty($document->metadata['type']) && $document->metadata['type'] === 'payment') {
            try {
                app(\App\Services\PaymentOrderService::class)->generate($document->fresh());
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /**
     * Obtenir le niveau actuel en attente
     */
    public function getCurrentLevel(Document $document): ?int
    {
        $pending = $document->approvals()
            ->where('status', 'pending')
            ->orderBy('level')
            ->first();

        return $pending ? $pending->level : null;
    }

    /**
     * Vérifier si un utilisateur peut approuver le niveau actuel
     */
    public function canUserApprove(Document $document, User $user): bool
    {
        if ($document->status !== DocumentStatus::Pending->value) {
            return false;
        }

        // Le rôle master peut toujours approuver n'importe quel niveau
        if ($user->hasRole('master')) {
            return $this->getCurrentLevel($document) !== null;
        }

        $currentLevel = $this->getCurrentLevel($document);
        if (!$currentLevel) {
            return false;
        }

        $approval = $document->approvals()
            ->where('level', $currentLevel)
            ->where('status', 'pending')
            ->with('workflowRule')
            ->first();

        if (!$approval || !$approval->workflowRule) {
            return false;
        }

        $approvers = $approval->workflowRule->getApprovers();
        return $approvers->pluck('id')->contains($user->id);
    }

    /**
     * Obtenir le libellé de progression (ex: "Étape 1/2")
     */
    public function getProgressLabel(Document $document): string
    {
        $totalLevels = WorkFlowRule::where('department_id', $document->department_id)
            ->where(function ($q) use ($document) {
                $q->where('category_id', $document->category_id)
                    ->orWhereNull('category_id');
            })
            ->where('is_active', true)
            ->count();

        $currentLevel = $this->getCurrentLevel($document);
        
        if (!$currentLevel) {
            return $document->status;
        }

        return "Étape $currentLevel/$totalLevels";
    }

    /**
     * Notifier les approbateurs d'un niveau
     */
    private function notifyLevelApprovers(Document $document, int $level): void
    {
        $approval = $document->approvals()
            ->where('level', $level)
            ->with('workflowRule')
            ->first();

        if ($approval && $approval->workflowRule) {
            $approvers = $approval->workflowRule->getApprovers();
            
            foreach ($approvers as $approver) {
                $notificationService = new NotificationService(
                    $document->title,
                    $approver,
                    $document
                );
                $notificationService->notifyBasedOnAction("level_{$level}_pending");
            }
        }
    }
}
