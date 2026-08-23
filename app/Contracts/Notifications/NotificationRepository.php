<?php

namespace App\Contracts\Notifications;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;

interface NotificationRepository
{
    /**
     * @return LengthAwarePaginator<int, DatabaseNotification>
     */
    public function paginateForUser(User $user, int $perPage): LengthAwarePaginator;

    public function unreadCountForUser(User $user): int;

    public function findForUser(User $user, string $uuid): DatabaseNotification;

    public function markAsRead(DatabaseNotification $notification): void;

    public function markAllAsRead(User $user): int;
}
