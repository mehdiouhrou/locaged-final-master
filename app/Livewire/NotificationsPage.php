<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;

class NotificationsPage extends Component
{
    use WithPagination;

    public function markAllAsRead(): void
    {
        auth()->user()?->unreadNotifications->markAsRead();
    }

    public function markAsReadAndRedirect(string $id, string $url)
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();

        return $this->redirect($url);
    }

    public function markAsRead(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->first()?->markAsRead();
    }

    public function deleteNotification(string $id): void
    {
        auth()->user()?->notifications()->where('id', $id)->delete();
    }

    public function render()
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(15);

        return view('livewire.notifications-page', compact('notifications'));
    }
}
