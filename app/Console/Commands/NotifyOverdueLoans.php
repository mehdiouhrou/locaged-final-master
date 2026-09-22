<?php

namespace App\Console\Commands;

use App\Models\DocumentMovement;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class NotifyOverdueLoans extends Command
{
    protected $signature = 'documents:notify-overdue-loans';

    protected $description = 'Notify borrowers of physical documents whose due date has passed (immediately on first overdue day, then weekly reminders)';

    public function handle(): int
    {
        $overdueLoans = DocumentMovement::query()
            ->openLoan()
            ->whereNotNull('due_at')
            ->where('due_at', '<', now())
            ->where(function ($q) {
                $q->whereNull('last_overdue_notified_at')
                    ->orWhere('last_overdue_notified_at', '<=', now()->subDays(7));
            })
            ->with(['document', 'borrowedBy'])
            ->get();

        $notified = 0;

        foreach ($overdueLoans as $loan) {
            $borrower = $loan->borrowedBy;
            $document = $loan->document;

            if (! $borrower || ! $document) {
                continue;
            }

            $notificationService = new NotificationService(
                $document->title,
                $borrower,
                $document->id,
                $document->latestVersion?->id
            );
            $notificationService->notifyBasedOnAction('loan_overdue');

            $loan->update(['last_overdue_notified_at' => now()]);
            $notified++;
        }

        $this->info("Checked overdue loans; {$notified} notification(s) sent.");

        return self::SUCCESS;
    }
}
