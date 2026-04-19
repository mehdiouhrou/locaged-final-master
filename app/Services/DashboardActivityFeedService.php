<?php

namespace App\Services;

use App\Enums\DocumentStatus;
use App\Models\Document;
use App\Models\DocumentStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Activité documentaire pour le tableau de bord : dépôts, attentes d’approbation,
 * décisions (approuvé / refusé) dans le périmètre visible de l’utilisateur.
 * (Pas le journal d’audit système.)
 */
class DashboardActivityFeedService
{
    /**
     * @return Collection<int, array{
     *   key: string,
     *   kind: string,
     *   document_id: int,
     *   title: string,
     *   category_id: int|null,
     *   status_code: string,
     *   status_label: string,
     *   at: \Carbon\Carbon,
     *   url: string|null,
     *   actor_name: string|null,
     *   actor_avatar: string|null,
     *   secondary_line: string|null
     * }>
     */
    public function feed(Builder $visibleDocumentsQuery, User $user, int $limit = 50): Collection
    {
        $visibleIdsSub = (clone $visibleDocumentsQuery)->select('documents.id');

        $uploads = (clone $visibleDocumentsQuery)
            ->where('documents.created_by', $user->id)
            ->with(['latestVersion:id,document_id,file_path'])
            ->orderByDesc('documents.created_at')
            ->limit(35)
            ->get()
            ->map(fn (Document $d) => $this->mapUpload($d, $user));

        $pending = (clone $visibleDocumentsQuery)
            ->where('documents.status', DocumentStatus::Pending->value)
            ->with(['createdBy:id,full_name,email,image', 'latestVersion:id,document_id,file_path'])
            ->orderByDesc('documents.updated_at')
            ->limit(35)
            ->get()
            ->map(fn (Document $d) => $this->mapPending($d, $user));

        $history = DocumentStatusHistory::query()
            ->with([
                'changedBy:id,full_name,email,image',
                'document' => function ($q) {
                    $q->with(['latestVersion:id,document_id,file_path']);
                },
            ])
            ->whereIn('to_status', [DocumentStatus::Approved->value, DocumentStatus::Declined->value])
            ->whereIn('document_id', $visibleIdsSub)
            ->orderByDesc('changed_at')
            ->limit(45)
            ->get()
            ->map(fn (DocumentStatusHistory $h) => $this->mapHistory($h))
            ->filter();

        return $uploads
            ->concat($pending)
            ->concat($history)
            ->sortByDesc(fn (array $row) => $row['at']->getTimestamp())
            ->values()
            ->take($limit);
    }

    private function mapUpload(Document $d, User $user): array
    {
        $at = $d->created_at ?? now();

        return [
            'key' => 'u-'.$d->id.'-'.$at->getTimestamp(),
            'kind' => 'upload',
            'document_id' => $d->id,
            'title' => $d->title,
            'category_id' => $d->category_id,
            'status_code' => (string) $d->status,
            'status_label' => $this->documentStatusLabel($d->status),
            'at' => $at,
            'url' => $this->documentUrl($d),
            'actor_name' => $user->full_name,
            'actor_avatar' => $user->avatar_url,
            'secondary_line' => __('Votre dépôt'),
        ];
    }

    private function mapPending(Document $d, User $viewer): array
    {
        $at = $d->updated_at ?? $d->created_at ?? now();
        $creator = $d->createdBy;
        $line = null;
        if ($creator && (int) $creator->id !== (int) $viewer->id) {
            $line = __('Déposé par :name', ['name' => $creator->full_name ?? $creator->email ?? '?']);
        }

        return [
            'key' => 'p-'.$d->id.'-'.$at->getTimestamp(),
            'kind' => 'pending',
            'document_id' => $d->id,
            'title' => $d->title,
            'category_id' => $d->category_id,
            'status_code' => DocumentStatus::Pending->value,
            'status_label' => __('En attente d’approbation'),
            'at' => $at,
            'url' => $this->documentUrl($d),
            'actor_name' => $creator?->full_name,
            'actor_avatar' => $creator?->avatar_url,
            'secondary_line' => $line,
        ];
    }

    private function mapHistory(?DocumentStatusHistory $h): ?array
    {
        if (! $h || ! $h->document) {
            return null;
        }

        $d = $h->document;
        $to = (string) $h->to_status;
        $kind = $to === DocumentStatus::Declined->value ? 'declined' : 'approved';
        $at = $h->changed_at ?? $h->created_at ?? now();
        $actor = $h->changedBy;

        return [
            'key' => 'h-'.$h->id,
            'kind' => $kind,
            'document_id' => $d->id,
            'title' => $d->title,
            'category_id' => $d->category_id,
            'status_code' => $to,
            'status_label' => $kind === 'declined'
                ? __('Refusé')
                : __('Approuvé'),
            'at' => $at,
            'url' => $this->documentUrl($d),
            'actor_name' => $actor?->full_name,
            'actor_avatar' => $actor?->avatar_url,
            'secondary_line' => $actor
                ? ($kind === 'declined'
                    ? __('Refus par :name', ['name' => $actor->full_name ?? $actor->email ?? '?'])
                    : __('Approbation par :name', ['name' => $actor->full_name ?? $actor->email ?? '?']))
                : null,
        ];
    }

    private function documentUrl(Document $d): ?string
    {
        if ($d->relationLoaded('latestVersion') && $d->latestVersion) {
            return route('document-versions.preview', ['id' => $d->latestVersion->id]);
        }

        $d->loadMissing('latestVersion:id,document_id,file_path');
        if ($d->latestVersion) {
            return route('document-versions.preview', ['id' => $d->latestVersion->id]);
        }

        return route('documents.show', $d);
    }

    /**
     * @param  mixed  $status
     */
    private function documentStatusLabel($status): string
    {
        $s = is_string($status) ? DocumentStatus::tryFrom($status) : null;
        if ($s === null) {
            return is_string($status) ? $status : (string) $status;
        }

        return match ($s) {
            DocumentStatus::Pending => __('En attente'),
            DocumentStatus::Approved => __('Approuvé'),
            DocumentStatus::Declined => __('Refusé'),
            DocumentStatus::Archived => __('Archivé'),
            DocumentStatus::Destroyed => __('Détruit'),
        };
    }
}
