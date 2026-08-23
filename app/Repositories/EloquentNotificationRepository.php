<?php

namespace App\Repositories;

use App\Contracts\Notifications\NotificationRepository;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentNotificationRepository implements NotificationRepository
{
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $user->notifications()
            ->latest()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function unreadCountForUser(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function findForUser(User $user, string $uuid): DatabaseNotification
    {
        return $user->notifications()->findOrFail($uuid);
    }

    public function markAsRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
