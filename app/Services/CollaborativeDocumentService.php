<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentComment;
use App\Models\DocumentReviewer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CollaborativeDocumentService
{
    /**
     * Assigne les relecteurs nommés à un document et le fait passer en relecture.
     *
     * @param  array<int, array{reviewer_id: int, deadline: ?string}>  $reviewers
     */
    public function assignReviewers(Document $document, array $reviewers): void
    {
        DB::transaction(function () use ($document, $reviewers) {
            foreach ($reviewers as $reviewer) {
                DocumentReviewer::updateOrCreate(
                    [
                        'document_id' => $document->id,
                        'reviewer_id' => $reviewer['reviewer_id'],
                    ],
                    [
                        'status' => 'pending',
                        'deadline' => $reviewer['deadline'] ?? null,
                        'comment' => null,
                        'responded_at' => null,
                    ]
                );
            }

            $document->status = 'en_relecture';
            $document->entry_type = 'collaborative';
            $document->save();
        });

        foreach ($document->reviewers()->with('reviewer')->get() as $documentReviewer) {
            $this->notify($documentReviewer->reviewer, $document, 'collab_reviewer_assigned');
        }
    }

    /**
     * Un relecteur valide le document. Si tous les relecteurs ont validé, le document passe à 'valide'.
     */
    public function reviewerValidate(Document $document, User $reviewer): void
    {
        $documentReviewer = $document->reviewers()->where('reviewer_id', $reviewer->id)->firstOrFail();

        $documentReviewer->update([
            'status' => 'validated',
            'responded_at' => now(),
        ]);

        if ($document->allReviewersValidated()) {
            $document->status = 'valide';
            $document->save();

            $this->notify($document->createdBy, $document, 'collab_document_validated');
        }
    }

    /**
     * Un relecteur rejette le document. Le document retombe en brouillon,
     * le motif est conservé sur la ligne du relecteur concerné.
     */
    public function reviewerReject(Document $document, User $reviewer, string $comment): void
    {
        $documentReviewer = $document->reviewers()->where('reviewer_id', $reviewer->id)->firstOrFail();

        $documentReviewer->update([
            'status' => 'rejected',
            'comment' => $comment,
            'responded_at' => now(),
        ]);

        DocumentComment::create([
            'document_id' => $document->id,
            'user_id' => $reviewer->id,
            'type' => 'reviewer_rejection',
            'comment' => $comment,
        ]);

        $document->status = 'brouillon';
        $document->save();

        $this->notify($document->createdBy, $document, 'collab_document_rejected');
    }

    /**
     * L'auteur resoumet le document après correction.
     * Tous les relecteurs repassent en attente (règle métier : tous doivent revalider).
     * Le fichier suit le comportement natif de versioning (nouvelle DocumentVersion).
     */
    public function resubmit(Document $document, ?string $comment = null): void
    {
        $document->resetReviewCycle();

        $meta = is_array($document->metadata) ? $document->metadata : (array) ($document->metadata ?? []);
        $comment = $comment !== null ? trim($comment) : '';
        if ($comment !== '') {
            $meta['resubmit_comment'] = $comment;

            DocumentComment::create([
                'document_id' => $document->id,
                'user_id' => $document->created_by,
                'type' => 'author_resubmit',
                'comment' => $comment,
            ]);
        } else {
            unset($meta['resubmit_comment']);
        }
        $document->metadata = $meta;

        $document->status = 'en_relecture';
        $document->save();

        foreach ($document->reviewers()->with('reviewer')->get() as $documentReviewer) {
            $this->notify($documentReviewer->reviewer, $document, 'collab_resubmitted');
        }
    }

    /**
     * Purge toutes les versions sauf la plus récente. À appeler au moment de la bascule
     * finale vers le pipeline archive (une fois la catégorie assignée après 'valide').
     */
    public function purgeIntermediateVersions(Document $document): void
    {
        $latest = $document->latestVersion;

        if (! $latest) {
            return;
        }

        $document->documentVersions()
            ->where('id', '!=', $latest->id)
            ->get()
            ->each(function ($version) {
                try {
                    if ($version->file_path && Storage::disk('local')->exists($version->file_path)) {
                        Storage::disk('local')->delete($version->file_path);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete intermediate version file', [
                        'version_id' => $version->id,
                        'error' => $e->getMessage(),
                    ]);
                }

                $version->delete();
            });
    }

    protected function notify(User $user, Document $document, string $action): void
    {
        try {
            (new NotificationService($document->title, $user, $document))
                ->notifyBasedOnAction($action);
        } catch (\Throwable $e) {
            Log::warning('Failed to send collaborative document notification', [
                'document_id' => $document->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
